<?php

use App\Enums\Rol;
use App\Models\Insumo;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;
use function Pest\Laravel\seed;

beforeEach(function () {
    seed(RolesSeeder::class);
});

function entrarAlCatalogoComo(Rol $rol): User
{
    $usuario = User::factory()->create();
    $usuario->assignRole($rol);
    Sanctum::actingAs($usuario);

    return $usuario;
}

function datosDeInsumo(array $cambios = []): array
{
    return array_merge([
        'codigo' => 'BEN-ARC',
        'nombre' => 'Bentonita',
        'categoria' => 'moldeo',
        'unidad' => 'kg',
        'costo_unitario' => 6.5,
        'stock_minimo' => 200,
    ], $cambios);
}

function insumoDeRetorno(): Insumo
{
    return Insumo::factory()->create([
        'codigo' => config('planta.insumo_retorno'),
        'categoria' => 'metal',
    ]);
}

it('crea un insumo con existencia en cero', function () {
    entrarAlCatalogoComo(Rol::Administrador);

    postJson('/api/insumos', datosDeInsumo(['codigo' => 'ben-arc']))
        ->assertCreated()
        ->assertJsonPath('codigo', 'BEN-ARC')
        ->assertJsonPath('existencia', '0.000')
        ->assertJsonPath('activo', true);
});

it('rechaza una categoría que no existe', function () {
    entrarAlCatalogoComo(Rol::Administrador);

    postJson('/api/insumos', datosDeInsumo(['categoria' => 'pintura']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['categoria' => 'La categoría debe ser metal, moldeo o empaque.']);
});

it('no modifica la existencia al editar', function () {
    entrarAlCatalogoComo(Rol::Administrador);
    $insumo = Insumo::factory()->create(['existencia' => 40]);

    putJson("/api/insumos/{$insumo->id}", datosDeInsumo([
        'codigo' => $insumo->codigo,
        'nombre' => 'Nombre editado',
        'existencia' => 999,
    ]))
        ->assertOk()
        ->assertJsonPath('nombre', 'Nombre editado')
        ->assertJsonPath('existencia', '40.000');
});

it('desactiva un insumo sin borrarlo', function () {
    entrarAlCatalogoComo(Rol::Administrador);
    $insumo = Insumo::factory()->create();

    deleteJson("/api/insumos/{$insumo->id}")->assertOk();

    expect($insumo->fresh()->activo)->toBeFalse();
});

it('no desactiva el insumo de retorno', function () {
    entrarAlCatalogoComo(Rol::Administrador);
    $retorno = insumoDeRetorno();

    deleteJson("/api/insumos/{$retorno->id}")
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['activo']);

    putJson("/api/insumos/{$retorno->id}", datosDeInsumo([
        'codigo' => $retorno->codigo,
        'categoria' => 'metal',
        'activo' => false,
    ]))->assertUnprocessable()->assertJsonValidationErrors(['activo']);

    expect($retorno->fresh()->activo)->toBeTrue();
});

it('no cambia el código ni la categoría del insumo de retorno', function () {
    entrarAlCatalogoComo(Rol::Administrador);
    $retorno = insumoDeRetorno();

    putJson("/api/insumos/{$retorno->id}", datosDeInsumo([
        'codigo' => 'OTRO-COD',
        'categoria' => 'moldeo',
    ]))->assertUnprocessable()->assertJsonValidationErrors(['codigo', 'categoria']);

    expect($retorno->fresh()->codigo)->toBe(config('planta.insumo_retorno'));
});

it('restringe el catálogo de insumos al administrador', function (Rol $rol) {
    entrarAlCatalogoComo($rol);

    getJson('/api/insumos')->assertForbidden();
    postJson('/api/insumos', datosDeInsumo())->assertForbidden();
})->with([Rol::Ventas, Rol::Encargado]);
