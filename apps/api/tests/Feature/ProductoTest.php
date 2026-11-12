<?php

use App\Models\Area;
use App\Models\Insumo;
use App\Models\Producto;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

function actuarComoProducto(string $rol): User
{
    Role::findOrCreate($rol, 'web');
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
        'recorrido' => collect(['MOL', 'FUS', 'COL', 'DES'])->map(fn ($c) => $areas[$c]->id)->all(),
        'receta' => [['insumo_id' => $insumo->id, 'cantidad_por_pieza' => '5.5700']],
        'activo' => true,
    ], $cambios);
}

beforeEach(function () {
    $this->areas = catalogoAreas();
    $this->insumo = Insumo::factory()->create(['activo' => true]);
    actuarComoProducto('administrador');
});

function ids(array $areas, string ...$codigos): array
{
    return array_map(fn ($c) => $areas[$c]->id, $codigos);
}

describe('recorrido', function () {
    it('guarda el orden del recorrido según la posición enviada', function () {
        $recorrido = ids($this->areas, 'MOL', 'FUS', 'COL', 'DES', 'GRA');

        $id = $this->postJson('/api/productos', payloadProducto($this->areas, $this->insumo, ['recorrido' => $recorrido]))
            ->assertCreated()
            ->json('id');

        foreach ($recorrido as $indice => $areaId) {
            $this->assertDatabaseHas('producto_area', [
                'producto_id' => $id,
                'area_id' => $areaId,
                'orden' => $indice + 1,
            ]);
        }
    });

    it('no crea el producto si el recorrido es inválido', function () {
        $this->postJson('/api/productos', payloadProducto($this->areas, $this->insumo, [
            'recorrido' => ids($this->areas, 'MOL', 'FUS'),
        ]))->assertUnprocessable();

        $this->assertDatabaseMissing('productos', ['codigo' => 'DIS-FRE-G']);
    });

    it('al editar borra las áreas del recorrido anterior', function () {
        $producto = Producto::factory()->create(['codigo' => 'DIS-FRE-G']);
        $producto->areas()->attach([
            $this->areas['MOL']->id => ['orden' => 1],
            $this->areas['GRA']->id => ['orden' => 2],
        ]);

        $this->putJson("/api/productos/{$producto->id}", payloadProducto($this->areas, $this->insumo))->assertOk();

        expect($producto->areas()->count())->toBe(4);
        $this->assertDatabaseMissing('producto_area', [
            'producto_id' => $producto->id,
            'area_id' => $this->areas['GRA']->id,
        ]);
    });

    it('acepta un recorrido válido con las obligatorias en orden', function () {
        $this->postJson('/api/productos', payloadProducto($this->areas, $this->insumo))
            ->assertCreated()
            ->assertJsonPath('recorrido.0.codigo', 'MOL')
            ->assertJsonPath('recorrido.3.codigo', 'DES')
            ->assertJsonPath('recorrido.3.orden', 4);
    });

    it('acepta áreas opcionales intercaladas sin alterar el orden de las obligatorias', function () {
        $recorrido = ids($this->areas, 'MOL', 'FUS', 'COL', 'DES', 'GRA', 'CAL');

        $this->postJson('/api/productos', payloadProducto($this->areas, $this->insumo, ['recorrido' => $recorrido]))
            ->assertCreated()
            ->assertJsonCount(6, 'recorrido');
    });

    it('rechaza un recorrido que no es una lista', function () {
        $this->postJson('/api/productos', payloadProducto($this->areas, $this->insumo, ['recorrido' => 'MOL,FUS']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['recorrido']);
    });

    it('rechaza ids de área que no son enteros', function () {
        $this->postJson('/api/productos', payloadProducto($this->areas, $this->insumo, ['recorrido' => ['abc']]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['recorrido.0']);
    });

    it('rechaza un recorrido al que le falta una obligatoria', function () {
        $recorrido = ids($this->areas, 'MOL', 'FUS', 'DES');

        $this->postJson('/api/productos', payloadProducto($this->areas, $this->insumo, ['recorrido' => $recorrido]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['recorrido'])
            ->assertJsonPath('errors.recorrido.0', 'Falta el área obligatoria COL.');
    });

    it('rechaza un recorrido al que le faltan varias obligatorias', function () {
        $recorrido = ids($this->areas, 'MOL', 'GRA');

        $this->postJson('/api/productos', payloadProducto($this->areas, $this->insumo, ['recorrido' => $recorrido]))
            ->assertUnprocessable()
            ->assertJsonPath('errors.recorrido.0', 'Faltan las áreas obligatorias FUS, COL, DES.');
    });

    it('rechaza obligatorias fuera de orden', function () {
        $recorrido = ids($this->areas, 'MOL', 'COL', 'FUS', 'DES');

        $this->postJson('/api/productos', payloadProducto($this->areas, $this->insumo, ['recorrido' => $recorrido]))
            ->assertUnprocessable()
            ->assertJsonPath('errors.recorrido.0', 'Las áreas obligatorias deben estar en este orden: MOL → FUS → COL → DES.');
    });

    it('rechaza un área inactiva', function () {
        $this->areas['GRA']->update(['activo' => false]);
        $recorrido = ids($this->areas, 'MOL', 'FUS', 'COL', 'DES', 'GRA');

        $this->postJson('/api/productos', payloadProducto($this->areas, $this->insumo, ['recorrido' => $recorrido]))
            ->assertUnprocessable()
            ->assertJsonPath('errors.recorrido.0', 'Las áreas GRA están inactivas y no pueden entrar en un recorrido nuevo.');
    });

    it('rechaza un recorrido vacío', function () {
        $this->postJson('/api/productos', payloadProducto($this->areas, $this->insumo, ['recorrido' => []]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['recorrido']);
    });

    it('rechaza un área repetida', function () {
        $recorrido = [...ids($this->areas, 'MOL', 'FUS', 'COL', 'DES'), $this->areas['MOL']->id];

        $this->postJson('/api/productos', payloadProducto($this->areas, $this->insumo, ['recorrido' => $recorrido]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['recorrido.4']);
    });

    it('rechaza un área que no existe', function () {
        $recorrido = [...ids($this->areas, 'MOL', 'FUS', 'COL', 'DES'), 99999];

        $this->postJson('/api/productos', payloadProducto($this->areas, $this->insumo, ['recorrido' => $recorrido]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['recorrido.4']);
    });

    it('rechaza un recorrido con solo áreas opcionales', function () {
        $this->postJson('/api/productos', payloadProducto($this->areas, $this->insumo, [
            'recorrido' => ids($this->areas, 'GRA', 'CAL'),
        ]))->assertUnprocessable()
            ->assertJsonPath('errors.recorrido.0', 'Faltan las áreas obligatorias MOL, FUS, COL, DES.');
    });

    it('lista todas las áreas inactivas en el mensaje', function () {
        $this->areas['GRA']->update(['activo' => false]);
        $this->areas['CAL']->update(['activo' => false]);

        $this->postJson('/api/productos', payloadProducto($this->areas, $this->insumo, [
            'recorrido' => ids($this->areas, 'MOL', 'FUS', 'COL', 'DES', 'GRA', 'CAL'),
        ]))->assertUnprocessable()
            ->assertJsonPath('errors.recorrido.0', 'Las áreas GRA, CAL están inactivas y no pueden entrar en un recorrido nuevo.');
    });
});

describe('receta', function () {
    it('rechaza una receta vacía', function () {
        $this->postJson('/api/productos', payloadProducto($this->areas, $this->insumo, ['receta' => []]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['receta']);
    });

    it('rechaza un insumo repetido', function () {
        $receta = [
            ['insumo_id' => $this->insumo->id, 'cantidad_por_pieza' => '1.0000'],
            ['insumo_id' => $this->insumo->id, 'cantidad_por_pieza' => '2.0000'],
        ];

        $this->postJson('/api/productos', payloadProducto($this->areas, $this->insumo, ['receta' => $receta]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['receta.0.insumo_id', 'receta.1.insumo_id']);
    });

    it('rechaza un insumo inactivo', function () {
        $inactivo = Insumo::factory()->create(['activo' => false]);
        $receta = [['insumo_id' => $inactivo->id, 'cantidad_por_pieza' => '1.0000']];

        $this->postJson('/api/productos', payloadProducto($this->areas, $this->insumo, ['receta' => $receta]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['receta.0.insumo_id']);
    });

    it('rechaza cantidades en cero, negativas o con más de 4 decimales', function (string|int|float $cantidad) {
        $receta = [['insumo_id' => $this->insumo->id, 'cantidad_por_pieza' => $cantidad]];

        $this->postJson('/api/productos', payloadProducto($this->areas, $this->insumo, ['receta' => $receta]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['receta.0.cantidad_por_pieza']);
    })->with([0, '0.0000', -1, '1.23456']);

    it('devuelve cantidad_por_pieza como texto (RNF-07)', function () {
        $this->postJson('/api/productos', payloadProducto($this->areas, $this->insumo))
            ->assertCreated()
            ->assertJsonPath('receta.0.cantidad_por_pieza', '5.5700');

        $cantidad = $this->getJson('/api/productos')->json('0.receta.0.cantidad_por_pieza');
        expect($cantidad)->toBeString();
    });
});

describe('edición', function () {
    it('conserva su propio código al editar y reemplaza el recorrido', function () {
        $producto = Producto::factory()->create(['codigo' => 'DIS-FRE-G']);
        $producto->areas()->attach($this->areas['MOL']->id, ['orden' => 1]);

        $recorrido = ids($this->areas, 'MOL', 'FUS', 'COL', 'DES', 'CAL');

        $this->putJson("/api/productos/{$producto->id}", payloadProducto($this->areas, $this->insumo, ['recorrido' => $recorrido]))
            ->assertOk()
            ->assertJsonCount(5, 'recorrido')
            ->assertJsonPath('recorrido.4.codigo', 'CAL');
    });

    it('aplica las mismas reglas de recorrido al editar', function () {
        $producto = Producto::factory()->create();

        $this->putJson("/api/productos/{$producto->id}", payloadProducto($this->areas, $this->insumo, [
            'codigo' => $producto->codigo,
            'recorrido' => ids($this->areas, 'FUS', 'MOL', 'COL', 'DES'),
        ]))->assertUnprocessable()->assertJsonValidationErrors(['recorrido']);
    });
});

describe('acceso', function () {
    it('responde 403 a roles distintos de administrador', function (string $rol) {
        actuarComoProducto($rol);
        $producto = Producto::factory()->create();
        $payload = payloadProducto($this->areas, $this->insumo);

        $this->getJson('/api/productos')->assertForbidden();
        $this->getJson('/api/productos/opciones')->assertForbidden();
        $this->postJson('/api/productos', $payload)->assertForbidden();
        $this->putJson("/api/productos/{$producto->id}", $payload)->assertForbidden();
        $this->deleteJson("/api/productos/{$producto->id}")->assertForbidden();
    })->with(['ventas', 'encargado', 'cliente']);

    it('responde 401 sin autenticar', function () {
        $this->app['auth']->forgetGuards();

        $this->getJson('/api/productos')->assertUnauthorized();
    });
});
