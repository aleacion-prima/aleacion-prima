<?php

use App\Enums\Rol;
use App\Models\Area;
use App\Models\Insumo;
use App\Models\Producto;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\patchJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;
use function Pest\Laravel\seed;

function actuarComoProducto(Rol $rol): User
{
    $usuario = User::factory()->create(['activo' => true]);
    $usuario->assignRole($rol);
    Sanctum::actingAs($usuario, ['*']);

    return $usuario;
}

function catalogoAreas(): array
{
    $areas = [];
    foreach ([['MOL', true], ['FUS', true], ['COL', true], ['DES', true], ['GRA', false], ['CAL', false]] as $i => [$codigo, $obligatoria]) {
        $areas[$codigo] = Area::factory()->create([
            'codigo' => $codigo,
            'orden_catalogo' => $i + 1,
            'obligatoria' => $obligatoria,
        ]);
    }

    return $areas;
}

/** @param array<string, mixed> $cambios */
function payloadProducto(array $areas, Insumo $insumo, array $cambios = []): array
{
    return array_merge([
        'codigo' => 'DIS-FRE-G',
        'nombre' => 'Disco de freno',
        'especificaciones' => ['material' => 'Hierro gris clase 30', 'peso_kg' => 8.5],
        'precio_mayoreo' => '280.00',
        'precio_menudeo' => '340.00',
        'recorrido' => idsDeRecorrido($areas, 'MOL', 'FUS', 'COL', 'DES'),
        'receta' => [['insumo_id' => $insumo->id, 'cantidad_por_pieza' => '5.5700']],
        'activo' => true,
    ], $cambios);
}

/** @return list<int> */
function idsDeRecorrido(array $areas, string ...$codigos): array
{
    return array_map(fn ($c) => $areas[$c]->id, $codigos);
}

beforeEach(function () {
    seed(RolesSeeder::class);
    $this->areas = catalogoAreas();
    $this->insumo = Insumo::factory()->create(['activo' => true]);
});

describe('recorrido', function () {
    beforeEach(function () {
        actuarComoProducto(Rol::Administrador);
    });

    it('guarda el orden del recorrido según la posición enviada', function () {
        $recorrido = idsDeRecorrido($this->areas, 'MOL', 'FUS', 'COL', 'DES', 'GRA');

        $id = postJson('/api/productos', payloadProducto($this->areas, $this->insumo, ['recorrido' => $recorrido]))
            ->assertCreated()
            ->json('id');

        foreach ($recorrido as $indice => $areaId) {
            assertDatabaseHas('producto_area', [
                'producto_id' => $id,
                'area_id' => $areaId,
                'orden' => $indice + 1,
            ]);
        }
    });

    it('no crea el producto si el recorrido es inválido', function () {
        postJson('/api/productos', payloadProducto($this->areas, $this->insumo, [
            'recorrido' => idsDeRecorrido($this->areas, 'MOL', 'FUS'),
        ]))->assertUnprocessable();

        assertDatabaseMissing('productos', ['codigo' => 'DIS-FRE-G']);
    });

    it('acepta un recorrido válido con las obligatorias en orden', function () {
        postJson('/api/productos', payloadProducto($this->areas, $this->insumo))
            ->assertCreated()
            ->assertJsonPath('recorrido.0.codigo', 'MOL')
            ->assertJsonPath('recorrido.3.codigo', 'DES')
            ->assertJsonPath('recorrido.3.orden', 4);
    });

    it('acepta áreas opcionales intercaladas sin alterar el orden de las obligatorias', function () {
        $recorrido = idsDeRecorrido($this->areas, 'MOL', 'FUS', 'COL', 'DES', 'GRA', 'CAL');

        postJson('/api/productos', payloadProducto($this->areas, $this->insumo, ['recorrido' => $recorrido]))
            ->assertCreated()
            ->assertJsonCount(6, 'recorrido');
    });

    it('rechaza un recorrido que no es una lista', function () {
        postJson('/api/productos', payloadProducto($this->areas, $this->insumo, ['recorrido' => 'MOL,FUS']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['recorrido']);
    });

    it('rechaza ids de área que no son enteros', function () {
        postJson('/api/productos', payloadProducto($this->areas, $this->insumo, ['recorrido' => ['abc']]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['recorrido.0']);
    });

    it('rechaza un recorrido al que le falta una obligatoria', function () {
        $recorrido = idsDeRecorrido($this->areas, 'MOL', 'FUS', 'DES');

        postJson('/api/productos', payloadProducto($this->areas, $this->insumo, ['recorrido' => $recorrido]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['recorrido'])
            ->assertJsonPath('errors.recorrido.0', 'Falta el área obligatoria COL.');
    });

    it('rechaza un recorrido al que le faltan varias obligatorias', function () {
        $recorrido = idsDeRecorrido($this->areas, 'MOL', 'GRA');

        postJson('/api/productos', payloadProducto($this->areas, $this->insumo, ['recorrido' => $recorrido]))
            ->assertUnprocessable()
            ->assertJsonPath('errors.recorrido.0', 'Faltan las áreas obligatorias FUS, COL, DES.');
    });

    it('rechaza obligatorias fuera de orden', function () {
        $recorrido = idsDeRecorrido($this->areas, 'MOL', 'COL', 'FUS', 'DES');

        postJson('/api/productos', payloadProducto($this->areas, $this->insumo, ['recorrido' => $recorrido]))
            ->assertUnprocessable()
            ->assertJsonPath('errors.recorrido.0', 'Las áreas obligatorias deben estar en este orden: MOL → FUS → COL → DES.');
    });

    it('rechaza un área inactiva', function () {
        $this->areas['GRA']->update(['activo' => false]);
        $recorrido = idsDeRecorrido($this->areas, 'MOL', 'FUS', 'COL', 'DES', 'GRA');

        postJson('/api/productos', payloadProducto($this->areas, $this->insumo, ['recorrido' => $recorrido]))
            ->assertUnprocessable()
            ->assertJsonPath('errors.recorrido.0', 'Las áreas GRA están inactivas y no pueden entrar en un recorrido nuevo.');
    });

    it('rechaza un recorrido vacío', function () {
        postJson('/api/productos', payloadProducto($this->areas, $this->insumo, ['recorrido' => []]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['recorrido']);
    });

    it('rechaza un área repetida', function () {
        $recorrido = [...idsDeRecorrido($this->areas, 'MOL', 'FUS', 'COL', 'DES'), $this->areas['MOL']->id];

        postJson('/api/productos', payloadProducto($this->areas, $this->insumo, ['recorrido' => $recorrido]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['recorrido.4']);
    });

    it('rechaza un área que no existe', function () {
        $recorrido = [...idsDeRecorrido($this->areas, 'MOL', 'FUS', 'COL', 'DES'), 99999];

        postJson('/api/productos', payloadProducto($this->areas, $this->insumo, ['recorrido' => $recorrido]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['recorrido.4']);
    });

    it('rechaza un recorrido con solo áreas opcionales', function () {
        postJson('/api/productos', payloadProducto($this->areas, $this->insumo, [
            'recorrido' => idsDeRecorrido($this->areas, 'GRA', 'CAL'),
        ]))->assertUnprocessable()
            ->assertJsonPath('errors.recorrido.0', 'Faltan las áreas obligatorias MOL, FUS, COL, DES.');
    });

    it('lista todas las áreas inactivas en el mensaje', function () {
        $this->areas['GRA']->update(['activo' => false]);
        $this->areas['CAL']->update(['activo' => false]);

        postJson('/api/productos', payloadProducto($this->areas, $this->insumo, [
            'recorrido' => idsDeRecorrido($this->areas, 'MOL', 'FUS', 'COL', 'DES', 'GRA', 'CAL'),
        ]))->assertUnprocessable()
            ->assertJsonPath('errors.recorrido.0', 'Las áreas GRA, CAL están inactivas y no pueden entrar en un recorrido nuevo.');
    });
});

describe('receta', function () {
    beforeEach(function () {
        actuarComoProducto(Rol::Administrador);
    });

    it('rechaza una receta vacía', function () {
        postJson('/api/productos', payloadProducto($this->areas, $this->insumo, ['receta' => []]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['receta']);
    });

    it('rechaza un insumo repetido', function () {
        $receta = [
            ['insumo_id' => $this->insumo->id, 'cantidad_por_pieza' => '1.0000'],
            ['insumo_id' => $this->insumo->id, 'cantidad_por_pieza' => '2.0000'],
        ];

        postJson('/api/productos', payloadProducto($this->areas, $this->insumo, ['receta' => $receta]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['receta.0.insumo_id', 'receta.1.insumo_id']);
    });

    it('rechaza un insumo inactivo', function () {
        $inactivo = Insumo::factory()->create(['activo' => false]);
        $receta = [['insumo_id' => $inactivo->id, 'cantidad_por_pieza' => '1.0000']];

        postJson('/api/productos', payloadProducto($this->areas, $this->insumo, ['receta' => $receta]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['receta.0.insumo_id']);
    });

    it('rechaza cantidades en cero, negativas o con más de 4 decimales', function (string|int|float $cantidad) {
        $receta = [['insumo_id' => $this->insumo->id, 'cantidad_por_pieza' => $cantidad]];

        postJson('/api/productos', payloadProducto($this->areas, $this->insumo, ['receta' => $receta]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['receta.0.cantidad_por_pieza']);
    })->with([0, '0.0000', -1, '1.23456']);

    it('devuelve cantidad_por_pieza como texto (RNF-07)', function () {
        postJson('/api/productos', payloadProducto($this->areas, $this->insumo))
            ->assertCreated()
            ->assertJsonPath('receta.0.cantidad_por_pieza', '5.5700');

        $cantidad = getJson('/api/productos')->json('0.receta.0.cantidad_por_pieza');
        expect($cantidad)->toBeString();
    });
});

describe('edición', function () {
    beforeEach(function () {
        actuarComoProducto(Rol::Administrador);
    });

    it('conserva su propio código y reemplaza el recorrido anterior', function () {
        $producto = Producto::factory()->create(['codigo' => 'DIS-FRE-G']);
        $producto->areas()->attach([
            $this->areas['MOL']->id => ['orden' => 1],
            $this->areas['GRA']->id => ['orden' => 2],
        ]);

        $recorrido = idsDeRecorrido($this->areas, 'MOL', 'FUS', 'COL', 'DES', 'CAL');

        putJson("/api/productos/{$producto->id}", payloadProducto($this->areas, $this->insumo, ['recorrido' => $recorrido]))
            ->assertOk()
            ->assertJsonCount(5, 'recorrido')
            ->assertJsonPath('recorrido.4.codigo', 'CAL');

        expect($producto->areas()->count())->toBe(5);
        assertDatabaseMissing('producto_area', [
            'producto_id' => $producto->id,
            'area_id' => $this->areas['GRA']->id,
        ]);
    });

    it('aplica las mismas reglas de recorrido al editar', function () {
        $producto = Producto::factory()->create();

        putJson("/api/productos/{$producto->id}", payloadProducto($this->areas, $this->insumo, [
            'codigo' => $producto->codigo,
            'recorrido' => idsDeRecorrido($this->areas, 'FUS', 'MOL', 'COL', 'DES'),
        ]))->assertUnprocessable()->assertJsonValidationErrors(['recorrido']);
    });
});

describe('acceso', function () {
    it('responde 403 a roles distintos de administrador y ventas', function (Rol $rol) {
        actuarComoProducto($rol);
        $producto = Producto::factory()->create();
        $payload = payloadProducto($this->areas, $this->insumo);

        getJson('/api/productos')->assertForbidden();
        getJson('/api/productos/opciones')->assertForbidden();
        postJson('/api/productos', $payload)->assertForbidden();
        putJson("/api/productos/{$producto->id}", $payload)->assertForbidden();
        deleteJson("/api/productos/{$producto->id}")->assertForbidden();
        patchJson("/api/productos/{$producto->id}/publicacion", ['publicado' => true])->assertForbidden();
    })->with([Rol::Encargado, Rol::Cliente]);

    describe('sin sesión', function () {
        it('responde 401 sin autenticar', function () {
            getJson('/api/productos')->assertUnauthorized();
        });
    });
});

it('ventas lee los productos y los publica, pero no los crea ni los edita', function () {
    actuarComoProducto(Rol::Ventas);
    $producto = Producto::factory()->create(['publicado' => false]);

    getJson('/api/productos')->assertOk()->assertJsonFragment(['id' => $producto->id]);
    getJson('/api/productos/opciones')->assertOk();

    patchJson("/api/productos/{$producto->id}/publicacion", ['publicado' => true])
        ->assertOk()
        ->assertJsonPath('publicado', true);
    expect($producto->fresh()->publicado)->toBeTrue();

    postJson('/api/productos', [])->assertForbidden();
    putJson("/api/productos/{$producto->id}", [])->assertForbidden();
    deleteJson("/api/productos/{$producto->id}")->assertForbidden();
});

it('publicar exige un booleano', function () {
    $producto = Producto::factory()->create();

    actuarComoProducto(Rol::Administrador);
    patchJson("/api/productos/{$producto->id}/publicacion", [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['publicado']);

});
