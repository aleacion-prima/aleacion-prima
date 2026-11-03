<?php

use App\Enums\EstadoLote;
use App\Enums\Rol;
use App\Enums\TipoMovimientoInsumo;
use App\Enums\TipoMovimientoProducto;
use App\Events\DisponibilidadActualizada;
use App\Events\LoteActualizado;
use App\Lotes\CapturaParada;
use App\Lotes\CreadorLote;
use App\Models\Area;
use App\Models\Insumo;
use App\Models\Lote;
use App\Models\MovimientoInsumo;
use App\Models\MovimientoProducto;
use App\Models\Producto;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\AreaSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\seed;
use function Pest\Laravel\travelTo;

beforeEach(function () {
    seed([RolesSeeder::class, AreaSeeder::class]);
    travelTo('2026-11-03 18:00:00');
});

function adminDeCierre(): User
{
    $admin = User::factory()->create();
    $admin->assignRole(Rol::Administrador);
    Sanctum::actingAs($admin);

    return $admin;
}

function loteParaCierre(User $admin): Lote
{
    $producto = Producto::factory()->create(['existencia' => 5]);

    foreach (['MOL', 'FUS', 'COL', 'DES'] as $indice => $codigo) {
        $producto->areas()->attach(Area::query()->where('codigo', $codigo)->value('id'), ['orden' => $indice + 1]);
    }

    $chatarra = Insumo::query()->where('codigo', 'CHT-CIE')->first() ?? Insumo::factory()->create([
        'codigo' => 'CHT-CIE',
        'categoria' => 'metal',
        'unidad' => 'kg',
        'costo_unitario' => 7,
        'existencia' => 1000,
    ]);
    $producto->insumos()->attach($chatarra->id, ['cantidad_por_pieza' => 10]);

    return app(CreadorLote::class)->crear($producto, 20, null, $admin);
}

function recorrerHasta(Lote $lote, User $admin, int $hasta, int $entregadas = 20): void
{
    $captura = app(CapturaParada::class);
    $hora = CarbonImmutable::parse('2026-11-03 08:00:00');

    foreach ($lote->paradas()->where('orden', '<=', $hasta)->get() as $parada) {
        $captura->entrada($lote, $parada, 20, $hora, $admin);
        $hora = $hora->addMinutes(30);
        $captura->salida($lote, $parada->fresh(), $entregadas, null, null, $hora, $admin);
    }
}

it('confirma el cierre y mete las piezas al inventario', function () {
    Event::fake([LoteActualizado::class, DisponibilidadActualizada::class]);
    $admin = adminDeCierre();
    $lote = loteParaCierre($admin);
    recorrerHasta($lote, $admin, 4, 18);

    postJson("/api/lotes/{$lote->codigo}/confirmar")
        ->assertOk()
        ->assertJsonPath('estado', 'terminado')
        ->assertJsonPath('piezas_terminadas', 18);

    $lote->refresh();
    expect($lote->confirmado_por)->toBe($admin->id)
        ->and($lote->confirmado_en)->not->toBeNull()
        ->and($lote->producto?->existencia)->toBe(23);

    $movimiento = MovimientoProducto::query()->firstOrFail();
    expect($movimiento->tipo)->toBe(TipoMovimientoProducto::Produccion)
        ->and($movimiento->cantidad)->toBe(18)
        ->and($movimiento->lote_id)->toBe($lote->id);

    Event::assertDispatched(
        DisponibilidadActualizada::class,
        fn (DisponibilidadActualizada $evento) => $evento->broadcastWith() === ['id' => $lote->producto_id, 'disponible' => 23],
    );
});

it('solo confirma lotes por confirmar', function () {
    $admin = adminDeCierre();
    $lote = loteParaCierre($admin);
    recorrerHasta($lote, $admin, 2);

    postJson("/api/lotes/{$lote->codigo}/confirmar")
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['lote']);

    expect(MovimientoProducto::query()->count())->toBe(0);
});

it('no confirma dos veces', function () {
    $admin = adminDeCierre();
    $lote = loteParaCierre($admin);
    recorrerHasta($lote, $admin, 4);

    postJson("/api/lotes/{$lote->codigo}/confirmar")->assertOk();
    postJson("/api/lotes/{$lote->codigo}/confirmar")->assertUnprocessable();

    expect($lote->producto?->fresh()?->existencia)->toBe(25);
});

it('cancela con motivo y cierra la parada abierta en cero', function () {
    $admin = adminDeCierre();
    $lote = loteParaCierre($admin);
    recorrerHasta($lote, $admin, 1);
    app(CapturaParada::class)->entrada($lote, $lote->paradas()->where('orden', 2)->firstOrFail(), 20, CarbonImmutable::parse('2026-11-03 09:00'), $admin);

    postJson("/api/lotes/{$lote->codigo}/cancelar", ['motivo' => 'Falla del horno'])
        ->assertOk()
        ->assertJsonPath('estado', 'cancelado')
        ->assertJsonPath('motivo_cancelacion', 'Falla del horno')
        ->assertJsonPath('area_actual', null)
        ->assertJsonPath('paradas.1.piezas_entregadas', 0)
        ->assertJsonPath('paradas.1.merma_piezas', 20);

    expect($lote->fresh()?->cancelado_por)->toBe($admin->id);
});

it('exige motivo para cancelar', function () {
    $admin = adminDeCierre();
    $lote = loteParaCierre($admin);

    postJson("/api/lotes/{$lote->codigo}/cancelar", [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['motivo']);
});

it('devuelve insumos si el lote no ha salido de su primera parada', function () {
    $admin = adminDeCierre();
    $lote = loteParaCierre($admin);
    $chatarra = Insumo::query()->where('codigo', 'CHT-CIE')->firstOrFail();
    expect($chatarra->existencia)->toBe('800.000');

    postJson("/api/lotes/{$lote->codigo}/cancelar", [
        'motivo' => 'Pedido cancelado',
        'devoluciones' => [['insumo_id' => $chatarra->id, 'cantidad' => 150.5]],
    ])->assertOk()->assertJsonPath('insumos.0.cantidad_devuelta', '150.500');

    expect($chatarra->fresh()?->existencia)->toBe('950.500');

    $devolucion = MovimientoInsumo::query()->where('tipo', TipoMovimientoInsumo::Devolucion)->firstOrFail();
    expect($devolucion->cantidad)->toBe('150.500')
        ->and($devolucion->costo_unitario)->toBe('7.0000')
        ->and($devolucion->lote_id)->toBe($lote->id);
});

it('no devuelve más de lo asignado ni después de la primera parada', function () {
    $admin = adminDeCierre();
    $lote = loteParaCierre($admin);
    $chatarra = Insumo::query()->where('codigo', 'CHT-CIE')->firstOrFail();

    postJson("/api/lotes/{$lote->codigo}/cancelar", [
        'motivo' => 'Prueba',
        'devoluciones' => [['insumo_id' => $chatarra->id, 'cantidad' => 200.001]],
    ])->assertUnprocessable()->assertJsonValidationErrors(['devoluciones.0.cantidad']);

    recorrerHasta($lote, $admin, 1);

    postJson("/api/lotes/{$lote->codigo}/cancelar", [
        'motivo' => 'Prueba',
        'devoluciones' => [['insumo_id' => $chatarra->id, 'cantidad' => 10]],
    ])->assertUnprocessable()->assertJsonValidationErrors(['devoluciones']);

    expect($lote->fresh()?->estado)->toBe(EstadoLote::EnProceso)
        ->and($chatarra->fresh()?->existencia)->toBe('800.000');
});

it('no cancela un lote terminado', function () {
    $admin = adminDeCierre();
    $lote = loteParaCierre($admin);
    recorrerHasta($lote, $admin, 4);
    postJson("/api/lotes/{$lote->codigo}/confirmar")->assertOk();

    postJson("/api/lotes/{$lote->codigo}/cancelar", ['motivo' => 'Tarde'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['lote']);
});

it('solo el administrador confirma o cancela', function (Rol $rol) {
    $admin = User::factory()->create();
    $admin->assignRole(Rol::Administrador);
    $lote = loteParaCierre($admin);

    $usuario = User::factory()->create();
    $usuario->assignRole($rol);
    Sanctum::actingAs($usuario);

    postJson("/api/lotes/{$lote->codigo}/confirmar")->assertForbidden();
    postJson("/api/lotes/{$lote->codigo}/cancelar", ['motivo' => 'x'])->assertForbidden();
})->with([Rol::Ventas, Rol::Encargado]);

it('dice si el lote todavía admite devolución', function () {
    $admin = adminDeCierre();
    $lote = loteParaCierre($admin);

    getJson("/api/lotes/{$lote->codigo}")->assertJsonPath('admite_devolucion', true);

    recorrerHasta($lote, $admin, 1);

    getJson("/api/lotes/{$lote->codigo}")->assertJsonPath('admite_devolucion', false);
});
