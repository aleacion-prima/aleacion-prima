<?php

use App\Enums\Rol;
use App\Models\Area;
use App\Models\User;
use Database\Seeders\AreaSeeder;
use Database\Seeders\RolesSeeder;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

beforeEach(function () {
    $this->seed(RolesSeeder::class);
});

function actuarComo(Rol $rol): User
{
    $usuario = User::factory()->create();
    $usuario->assignRole($rol);
    Sanctum::actingAs($usuario);

    return $usuario;
}

function datosDeArea(array $cambios = []): array
{
    return array_merge([
        'codigo' => 'PIN',
        'nombre' => 'Pintura y acabado',
        'descripcion' => 'Cabina de pintura',
        'tarifa_mano_obra_hora' => 140.5,
        'costo_maquina_hora' => 95,
        'obligatoria' => false,
        'orden_catalogo' => 8,
        'activo' => true,
    ], $cambios);
}

describe('control de acceso', function () {
    it('pide sesión a quien no ha entrado', function () {
        getJson('/api/areas')->assertUnauthorized();
    });

    it('responde 403 a todos los roles salvo administrador', function (Rol $rol) {
        $area = Area::factory()->create();
        actuarComo($rol);

        getJson('/api/areas')->assertForbidden();
        getJson("/api/areas/{$area->id}")->assertForbidden();
        postJson('/api/areas', datosDeArea())->assertForbidden();
        putJson("/api/areas/{$area->id}", datosDeArea())->assertForbidden();
        deleteJson("/api/areas/{$area->id}")->assertForbidden();
    })->with([Rol::Ventas, Rol::Encargado, Rol::Cliente]);

    it('no cambia las tarifas cuando Ventas o Encargado lo intentan', function (Rol $rol) {
        $area = Area::factory()->create(['tarifa_mano_obra_hora' => 100, 'costo_maquina_hora' => 50]);
        actuarComo($rol);

        putJson("/api/areas/{$area->id}", datosDeArea([
            'codigo' => $area->codigo,
            'nombre' => $area->nombre,
            'tarifa_mano_obra_hora' => 999,
        ]))->assertForbidden();

        expect($area->refresh()->tarifa_mano_obra_hora)->toBe('100.0000');
    })->with([Rol::Ventas, Rol::Encargado]);
});

describe('listado', function () {
    beforeEach(function () {
        actuarComo(Rol::Administrador);
    });

    it('lista las áreas en el orden del catálogo con las tarifas completas', function () {
        Area::factory()->create(['codigo' => 'BBB', 'orden_catalogo' => 2]);
        Area::factory()->create(['codigo' => 'AAA', 'orden_catalogo' => 1, 'tarifa_mano_obra_hora' => 180]);

        getJson('/api/areas')
            ->assertOk()
            ->assertJsonPath('0.codigo', 'AAA')
            ->assertJsonPath('0.tarifa_mano_obra_hora', '180.0000')
            ->assertJsonPath('1.codigo', 'BBB')
            ->assertJsonStructure(['*' => ['id', 'codigo', 'nombre', 'descripcion', 'tarifa_mano_obra_hora', 'costo_maquina_hora', 'obligatoria', 'orden_catalogo', 'activo']]);
    });

    it('filtra por activo para los recorridos nuevos', function () {
        Area::factory()->create(['codigo' => 'ACT']);
        Area::factory()->inactiva()->create(['codigo' => 'INA']);

        expect(collect(getJson('/api/areas?activo=1')->json())->pluck('codigo')->all())->toBe(['ACT'])
            ->and(collect(getJson('/api/areas?activo=0')->json())->pluck('codigo')->all())->toBe(['INA'])
            ->and(getJson('/api/areas')->json())->toHaveCount(2);
    });
});

describe('alta', function () {
    beforeEach(function () {
        actuarComo(Rol::Administrador);
    });

    it('crea un área y normaliza el código a mayúsculas', function () {
        postJson('/api/areas', datosDeArea(['codigo' => ' pin ']))
            ->assertCreated()
            ->assertJsonPath('codigo', 'PIN')
            ->assertJsonPath('tarifa_mano_obra_hora', '140.5000')
            ->assertJsonPath('costo_maquina_hora', '95.0000')
            ->assertJsonPath('activo', true);

        $this->assertDatabaseHas('areas', ['codigo' => 'PIN', 'orden_catalogo' => 8]);
    });

    it('valida campos obligatorios', function () {
        postJson('/api/areas', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['codigo', 'nombre', 'tarifa_mano_obra_hora', 'costo_maquina_hora', 'obligatoria', 'orden_catalogo']);
    });

    it('rechaza código, nombre y orden repetidos', function () {
        Area::factory()->create(['codigo' => 'PIN', 'nombre' => 'Pintura y acabado', 'orden_catalogo' => 8]);

        postJson('/api/areas', datosDeArea())
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['codigo', 'nombre', 'orden_catalogo']);
    });

    it('rechaza tarifas negativas, con demasiados decimales o que no son número', function (mixed $valor) {
        postJson('/api/areas', datosDeArea(['tarifa_mano_obra_hora' => $valor, 'costo_maquina_hora' => $valor]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['tarifa_mano_obra_hora', 'costo_maquina_hora']);
    })->with([-1, 10.12345, 'mucho']);

    it('rechaza códigos con espacios o símbolos', function () {
        postJson('/api/areas', datosDeArea(['codigo' => 'P N']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('codigo');
    });
});

describe('edición de tarifas', function () {
    beforeEach(function () {
        actuarComo(Rol::Administrador);
    });

    it('edita las tarifas de mano de obra y de máquina', function () {
        $area = Area::factory()->create(['tarifa_mano_obra_hora' => 180, 'costo_maquina_hora' => 220]);

        putJson("/api/areas/{$area->id}", datosDeArea([
            'codigo' => $area->codigo,
            'nombre' => $area->nombre,
            'orden_catalogo' => $area->orden_catalogo,
            'tarifa_mano_obra_hora' => 195.75,
            'costo_maquina_hora' => 230,
        ]))->assertOk()
            ->assertJsonPath('tarifa_mano_obra_hora', '195.7500')
            ->assertJsonPath('costo_maquina_hora', '230.0000');

        expect($area->refresh()->tarifa_mano_obra_hora)->toBe('195.7500');
    });

    it('permite conservar el propio código, nombre y orden', function () {
        $area = Area::factory()->create();

        putJson("/api/areas/{$area->id}", datosDeArea([
            'codigo' => $area->codigo,
            'nombre' => $area->nombre,
            'orden_catalogo' => $area->orden_catalogo,
        ]))->assertOk();
    });

    it('no cambia las tarifas congeladas de los lotes existentes', function () {
        // Pendiente hasta que exista el servicio que crea lotes (ADR-0009):
        // crear un lote, editar la tarifa del área y comprobar que lote_areas conserva la copia.
    })->todo();
});

describe('desactivación', function () {
    beforeEach(function () {
        actuarComo(Rol::Administrador);
    });

    it('desactiva sin borrar la fila y deja de salir para recorridos nuevos', function () {
        $area = Area::factory()->create(['codigo' => 'GRA']);

        deleteJson("/api/areas/{$area->id}")->assertNoContent();

        $this->assertDatabaseHas('areas', ['id' => $area->id, 'activo' => false]);
        expect(getJson('/api/areas?activo=1')->json())->toBeEmpty()
            ->and(getJson('/api/areas?activo=0')->json())->toHaveCount(1);
    });

    it('se reactiva editando con activo = true', function () {
        $area = Area::factory()->inactiva()->create();

        putJson("/api/areas/{$area->id}", datosDeArea([
            'codigo' => $area->codigo,
            'nombre' => $area->nombre,
            'orden_catalogo' => $area->orden_catalogo,
            'activo' => true,
        ]))->assertOk()->assertJsonPath('activo', true);
    });

    it('no deja desactivar un área obligatoria', function () {
        $area = Area::factory()->obligatoria()->create();

        deleteJson("/api/areas/{$area->id}")->assertUnprocessable()->assertJsonValidationErrors('activo');

        putJson("/api/areas/{$area->id}", datosDeArea([
            'codigo' => $area->codigo,
            'nombre' => $area->nombre,
            'orden_catalogo' => $area->orden_catalogo,
            'obligatoria' => true,
            'activo' => false,
        ]))->assertUnprocessable()->assertJsonValidationErrors('activo');

        expect($area->refresh()->activo)->toBeTrue();
    });
});

describe('seeder', function () {
    it('crea las 7 áreas de empresa.md con sus tarifas', function () {
        $this->seed(AreaSeeder::class);

        expect(Area::count())->toBe(7);

        $esperadas = [
            'MOL' => ['180.0000', '220.0000', true, 1],
            'FUS' => ['220.0000', '450.0000', true, 2],
            'COL' => ['60.0000', '80.0000', true, 3],
            'DES' => ['160.0000', '180.0000', true, 4],
            'GRA' => ['170.0000', '200.0000', false, 5],
            'CAL' => ['250.0000', '300.0000', false, 6],
            'EMP' => ['150.0000', '40.0000', false, 7],
        ];

        foreach ($esperadas as $codigo => [$manoObra, $maquina, $obligatoria, $orden]) {
            $area = Area::where('codigo', $codigo)->firstOrFail();
            expect($area->tarifa_mano_obra_hora)->toBe($manoObra)
                ->and($area->costo_maquina_hora)->toBe($maquina)
                ->and($area->obligatoria)->toBe($obligatoria)
                ->and($area->orden_catalogo)->toBe($orden)
                ->and($area->activo)->toBeTrue();
        }
    });

    it('es idempotente', function () {
        $this->seed(AreaSeeder::class);
        $this->seed(AreaSeeder::class);

        expect(Area::count())->toBe(7);
    });
});
