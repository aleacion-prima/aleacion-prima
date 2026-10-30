<?php

use App\Enums\Rol;
use App\Models\Ajuste;
use App\Models\Insumo;
use App\Models\MovimientoInsumo;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Support\Facades\Artisan;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;
use function Pest\Laravel\seed;

beforeEach(function () {
    seed(RolesSeeder::class);
});

function usuarioInventario(Rol $rol): User
{
    $usuario = User::factory()->create();
    $usuario->assignRole($rol);

    return $usuario;
}

function insumoInventario(array $atributos = []): Insumo
{
    return Insumo::query()->create(array_merge([
        'codigo' => fake()->unique()->lexify('INS-???'),
        'nombre' => fake()->unique()->words(2, true),
        'categoria' => 'metal',
        'unidad' => 'kg',
        'costo_unitario' => 10,
        'stock_minimo' => 5,
        'activo' => true,
    ], $atributos));
}

it('registra una entrada y actualiza la existencia en una transacción', function () {
    Sanctum::actingAs(usuarioInventario(Rol::Administrador));
    $insumo = insumoInventario(['existencia' => 10]);

    postJson("/api/insumos/{$insumo->id}/entradas", [
        'cantidad' => 12.5,
        'costo_unitario' => 11.25,
        'proveedor' => 'Proveedor de prueba',
        'lote_proveedor' => 'LOTE-001',
    ])
        ->assertCreated()
        ->assertJsonPath('tipo', 'entrada')
        ->assertJsonPath('proveedor', 'Proveedor de prueba')
        ->assertJsonPath('lote_proveedor', 'LOTE-001');

    expect($insumo->fresh()->existencia)->toBe('22.500');
    expect(MovimientoInsumo::query()->where('insumo_id', $insumo->id)->count())->toBe(1);
});

it('permite a ventas consultar insumos y productos, pero no registrar entradas', function () {
    $usuario = usuarioInventario(Rol::Ventas);
    Sanctum::actingAs($usuario);
    $insumo = insumoInventario(['existencia' => 4]);

    getJson('/api/inventario/insumos')
        ->assertOk()
        ->assertJsonPath('0.id', $insumo->id);
    getJson('/api/inventario/productos')->assertOk();
    postJson("/api/insumos/{$insumo->id}/entradas", [
        'cantidad' => 1,
        'costo_unitario' => 10,
        'proveedor' => 'Proveedor',
    ])->assertForbidden();
});

it('rechaza al encargado para consultar existencias', function () {
    Sanctum::actingAs(usuarioInventario(Rol::Encargado));

    getJson('/api/inventario/insumos')->assertForbidden();
    getJson('/api/inventario/productos')->assertForbidden();
});

it('reconstruye existencias desde los movimientos', function () {
    $insumo = insumoInventario(['existencia' => 999]);
    $usuario = usuarioInventario(Rol::Administrador);

    $insumo->movimientos()->createMany([
        ['tipo' => 'entrada', 'cantidad' => 20, 'costo_unitario' => 10, 'user_id' => $usuario->id, 'ocurrido_en' => now()],
        ['tipo' => 'asignacion', 'cantidad' => -7.5, 'user_id' => $usuario->id, 'ocurrido_en' => now()],
    ]);

    Artisan::call('inventario:recalcular');

    expect($insumo->fresh()->existencia)->toBe('12.500');
});

it('solo expone lectura de movimientos y no rutas de edición o borrado', function () {
    $usuario = usuarioInventario(Rol::Administrador);
    Sanctum::actingAs($usuario);
    $insumo = insumoInventario();

    getJson("/api/insumos/{$insumo->id}/movimientos")->assertOk();
    putJson("/api/insumos/{$insumo->id}/movimientos/1")->assertNotFound();
    deleteJson("/api/insumos/{$insumo->id}/movimientos/1")->assertNotFound();
});

it('registra un ajuste positivo con motivo, valores y movimiento firmado', function () {
    Sanctum::actingAs(usuarioInventario(Rol::Administrador));
    $insumo = insumoInventario(['existencia' => 10]);

    postJson("/api/insumos/{$insumo->id}/ajustes", [
        'cantidad_real' => 13.5,
        'motivo' => 'Conteo físico de auditoría',
    ])
        ->assertCreated()
        ->assertJsonPath('campo', 'existencia')
        ->assertJsonPath('valores.anterior', 10)
        ->assertJsonPath('valores.nuevo', 13.5)
        ->assertJsonPath('motivo', 'Conteo físico de auditoría')
        ->assertJsonPath('movimiento.tipo', 'ajuste')
        ->assertJsonPath('movimiento.cantidad', '3.500');

    expect($insumo->fresh()->existencia)->toBe('13.500');
    expect(Ajuste::query()->where('insumo_id', $insumo->id)->count())->toBe(1);
});

it('registra un ajuste negativo y deja la existencia en la cantidad real', function () {
    Sanctum::actingAs(usuarioInventario(Rol::Administrador));
    $insumo = insumoInventario(['existencia' => 10]);

    postJson("/api/insumos/{$insumo->id}/ajustes", [
        'cantidad_real' => 6.25,
        'motivo' => 'Merma detectada en auditoría',
    ])->assertCreated();

    expect($insumo->fresh()->existencia)->toBe('6.250');
    expect(MovimientoInsumo::query()->where('insumo_id', $insumo->id)->first()?->cantidad)
        ->toBe('-3.750');
});

it('solo permite ajustes al administrador', function (Rol $rol) {
    Sanctum::actingAs(usuarioInventario($rol));
    $insumo = insumoInventario(['existencia' => 10]);

    postJson("/api/insumos/{$insumo->id}/ajustes", [
        'cantidad_real' => 12,
        'motivo' => 'No autorizado',
    ])->assertForbidden();
})->with([Rol::Ventas, Rol::Encargado]);
