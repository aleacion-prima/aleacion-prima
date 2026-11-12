<?php

use App\Enums\EstadoLote;
use App\Enums\Rol;
use App\Enums\TipoMovimientoProducto;
use App\Events\DisponibilidadActualizada;
use App\Events\LoteActualizado;
use App\Lotes\CalculadorCostoLote;
use App\Lotes\CapturaParada;
use App\Lotes\CreadorLote;
use App\Models\Ajuste;
use App\Models\Area;
use App\Models\Insumo;
use App\Models\Lote;
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

function adminDeAjustes(): User
{
    $admin = User::factory()->create();
    $admin->assignRole(Rol::Administrador);
    Sanctum::actingAs($admin);

    return $admin;
}

function loteParaAjustes(User $admin): Lote
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

function recorrerParaAjustes(Lote $lote, User $admin, int $hasta, int $entregadas = 20): void
{
    $captura = app(CapturaParada::class);
    $hora = CarbonImmutable::parse('2026-11-03 08:00:00');

    foreach ($lote->paradas()->where('orden', '<=', $hasta)->get() as $parada) {
        $captura->entrada($lote, $parada, 20, $hora, $admin);
        $hora = $hora->addMinutes(30);
        $captura->salida($lote, $parada->fresh(), $entregadas, null, null, $hora, $admin);
    }
}

function loteTerminadoParaAjustes(User $admin): Lote
{
    $lote = loteParaAjustes($admin);
    recorrerParaAjustes($lote, $admin, 4, 18);

    postJson("/api/lotes/{$lote->codigo}/confirmar")->assertOk();

    return $lote->refresh();
}

it('exige motivo para ajustar un lote', function () {
    $admin = adminDeAjustes();
    $lote = loteParaAjustes($admin);
    $asignado = $lote->insumos()->firstOrFail();

    postJson("/api/lotes/{$lote->codigo}/ajustes", [
        'lote_insumo_id' => $asignado->id,
        'campo' => 'cantidad',
        'valor' => 210,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('motivo');

    expect(Ajuste::query()->count())->toBe(0);
});

it('solo permite ajustes al administrador', function (Rol $rol) {
    $usuario = User::factory()->create();
    $usuario->assignRole($rol);
    Sanctum::actingAs($usuario);
    $lote = loteParaAjustes($usuario);
    $asignado = $lote->insumos()->firstOrFail();

    postJson("/api/lotes/{$lote->codigo}/ajustes", [
        'lote_insumo_id' => $asignado->id,
        'campo' => 'cantidad',
        'valor' => 210,
        'motivo' => 'No autorizado',
    ])->assertForbidden();
})->with([Rol::Ventas, Rol::Encargado]);

it('ajusta un insumo asignado y recalcula el costo', function () {
    Event::fake([LoteActualizado::class]);
    $admin = adminDeAjustes();
    $lote = loteParaAjustes($admin);
    $asignado = $lote->insumos()->firstOrFail();

    postJson("/api/lotes/{$lote->codigo}/ajustes", [
        'lote_insumo_id' => $asignado->id,
        'campo' => 'cantidad',
        'valor' => 210,
        'motivo' => 'Pesaje corregido en báscula',
    ])
        ->assertCreated()
        ->assertJsonPath('ajuste.insumo.insumo_id', $asignado->insumo_id)
        ->assertJsonPath('ajuste.valores.anterior', '200.000')
        ->assertJsonPath('ajuste.valores.nuevo', '210')
        ->assertJsonPath('ajuste.motivo', 'Pesaje corregido en báscula')
        ->assertJsonPath('lote.codigo', $lote->codigo);

    expect($asignado->fresh()?->cantidad)->toBe('210.000')
        ->and((float) $lote->fresh()?->costo_material)->toBe(1470.0);
});

it('ajusta una parada y recalcula la mano de obra con las horas nuevas', function () {
    Event::fake([LoteActualizado::class]);
    $admin = adminDeAjustes();
    $lote = loteParaAjustes($admin);
    recorrerParaAjustes($lote, $admin, 1);
    app(CalculadorCostoLote::class)->recalcular($lote);

    $parada = $lote->paradas()->firstOrFail();
    expect((float) $lote->fresh()?->costo_mano_obra)->toBe(90.0);

    postJson("/api/lotes/{$lote->codigo}/ajustes", [
        'lote_area_id' => $parada->id,
        'campo' => 'salida_ocurrido_en',
        'valor' => CarbonImmutable::parse('2026-11-03 09:30:00')->toIso8601String(),
        'motivo' => 'La salida se registró una hora antes',
    ])
        ->assertCreated()
        ->assertJsonPath('ajuste.parada.area.codigo', 'MOL')
        ->assertJsonPath('ajuste.valores.anterior', $parada->salida_ocurrido_en?->toIso8601String())
        ->assertJsonPath('lote.estado', 'en_proceso');

    expect((float) $lote->fresh()?->costo_mano_obra)->toBe(270.0)
        ->and((float) $lote->fresh()?->costo_maquina)->toBe(330.0);
});

it('recalcula la merma en piezas cuando cambian las piezas', function () {
    $admin = adminDeAjustes();
    $lote = loteParaAjustes($admin);
    recorrerParaAjustes($lote, $admin, 1, 18);

    $parada = $lote->paradas()->firstOrFail();
    expect($parada->merma_piezas)->toBe(2);

    postJson("/api/lotes/{$lote->codigo}/ajustes", [
        'lote_area_id' => $parada->id,
        'campo' => 'piezas_entregadas',
        'valor' => 15,
        'motivo' => 'Tres piezas se rompieron al desmoldar',
    ])->assertCreated();

    expect($parada->fresh()?->merma_piezas)->toBe(5);
});

it('rechaza ajustar un dato que todavía no existe en la parada', function () {
    $admin = adminDeAjustes();
    $lote = loteParaAjustes($admin);
    $parada = $lote->paradas()->firstOrFail();
    app(CapturaParada::class)->entrada($lote, $parada, 20, CarbonImmutable::parse('2026-11-03 08:00'), $admin);

    postJson("/api/lotes/{$lote->codigo}/ajustes", [
        'lote_area_id' => $parada->id,
        'campo' => 'piezas_entregadas',
        'valor' => 18,
        'motivo' => 'Todavía no hay salida',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('campo');

    expect($parada->fresh()?->piezas_entregadas)->toBeNull();
});

it('no deja entregar más piezas de las recibidas', function () {
    $admin = adminDeAjustes();
    $lote = loteParaAjustes($admin);
    recorrerParaAjustes($lote, $admin, 1, 18);
    $parada = $lote->paradas()->firstOrFail();

    postJson("/api/lotes/{$lote->codigo}/ajustes", [
        'lote_area_id' => $parada->id,
        'campo' => 'piezas_entregadas',
        'valor' => 25,
        'motivo' => 'Corrección imposible',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('valor');

    expect($parada->fresh()?->piezas_entregadas)->toBe(18);
});

it('rechaza un ajuste que no cambia nada', function () {
    $admin = adminDeAjustes();
    $lote = loteParaAjustes($admin);
    $asignado = $lote->insumos()->firstOrFail();

    postJson("/api/lotes/{$lote->codigo}/ajustes", [
        'lote_insumo_id' => $asignado->id,
        'campo' => 'cantidad',
        'valor' => 200,
        'motivo' => 'Sin diferencias',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('valor');

    expect(Ajuste::query()->count())->toBe(0);
});

it('rechaza campos que no se ajustan en ese renglón', function () {
    $admin = adminDeAjustes();
    $lote = loteParaAjustes($admin);
    $asignado = $lote->insumos()->firstOrFail();

    postJson("/api/lotes/{$lote->codigo}/ajustes", [
        'lote_insumo_id' => $asignado->id,
        'campo' => 'orden',
        'valor' => 2,
        'motivo' => 'Campo inválido',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('campo');
});

it('rechaza una parada o un insumo de otro lote', function () {
    $admin = adminDeAjustes();
    $lote = loteParaAjustes($admin);
    $otro = loteParaAjustes($admin);

    postJson("/api/lotes/{$lote->codigo}/ajustes", [
        'lote_area_id' => $otro->paradas()->firstOrFail()->id,
        'campo' => 'piezas_entregadas',
        'valor' => 19,
        'motivo' => 'Parada de otro lote',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('lote_area_id');

    postJson("/api/lotes/{$lote->codigo}/ajustes", [
        'lote_insumo_id' => $otro->insumos()->firstOrFail()->id,
        'campo' => 'cantidad',
        'valor' => 190,
        'motivo' => 'Insumo de otro lote',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('lote_insumo_id');
});

it('corrige las piezas terminadas y el inventario de la última parada de un lote terminado', function () {
    Event::fake([LoteActualizado::class, DisponibilidadActualizada::class]);
    $admin = adminDeAjustes();
    $lote = loteTerminadoParaAjustes($admin);
    $ultima = $lote->paradas()->reorder('orden', 'desc')->firstOrFail();

    expect($lote->estado)->toBe(EstadoLote::Terminado)
        ->and($lote->piezas_terminadas)->toBe(18)
        ->and($lote->producto->existencia)->toBe(23);

    postJson("/api/lotes/{$lote->codigo}/ajustes", [
        'lote_area_id' => $ultima->id,
        'campo' => 'piezas_entregadas',
        'valor' => 20,
        'motivo' => 'Se contaron dos piezas más al empacar',
    ])
        ->assertCreated()
        ->assertJsonPath('ajuste.valores.anterior', 18)
        ->assertJsonPath('ajuste.valores.nuevo', 20)
        ->assertJsonPath('lote.estado', 'terminado')
        ->assertJsonPath('lote.piezas_terminadas', 20);

    expect($lote->fresh()?->piezas_terminadas)->toBe(20)
        ->and($lote->producto->fresh()?->existencia)->toBe(25);

    $movimiento = MovimientoProducto::query()
        ->where('tipo', TipoMovimientoProducto::Ajuste)
        ->firstOrFail();

    expect($movimiento->cantidad)->toBe(2)
        ->and($movimiento->lote_id)->toBe($lote->id)
        ->and($movimiento->motivo)->toBe('Se contaron dos piezas más al empacar');

    Event::assertDispatched(
        DisponibilidadActualizada::class,
        fn (DisponibilidadActualizada $evento) => $evento->broadcastWith() === ['id' => $lote->producto_id, 'disponible' => 25],
    );
});

it('resta del inventario cuando el ajuste baja las piezas terminadas', function () {
    Event::fake([LoteActualizado::class, DisponibilidadActualizada::class]);
    $admin = adminDeAjustes();
    $lote = loteTerminadoParaAjustes($admin);
    $ultima = $lote->paradas()->reorder('orden', 'desc')->firstOrFail();

    postJson("/api/lotes/{$lote->codigo}/ajustes", [
        'lote_area_id' => $ultima->id,
        'campo' => 'piezas_entregadas',
        'valor' => 15,
        'motivo' => 'Tres piezas salieron defectuosas',
    ])->assertCreated();

    expect($lote->fresh()?->piezas_terminadas)->toBe(15)
        ->and($lote->producto->fresh()?->existencia)->toBe(20);

    $movimiento = MovimientoProducto::query()
        ->where('tipo', TipoMovimientoProducto::Ajuste)
        ->firstOrFail();

    expect($movimiento->cantidad)->toBe(-3);
});

it('no cambia las piezas terminadas al ajustar una parada que no es la última', function () {
    Event::fake([LoteActualizado::class, DisponibilidadActualizada::class]);
    $admin = adminDeAjustes();
    $lote = loteTerminadoParaAjustes($admin);
    $primera = $lote->paradas()->firstOrFail();

    postJson("/api/lotes/{$lote->codigo}/ajustes", [
        'lote_area_id' => $primera->id,
        'campo' => 'piezas_entregadas',
        'valor' => 19,
        'motivo' => 'Corrección en moldeo',
    ])->assertCreated();

    expect($lote->fresh()?->piezas_terminadas)->toBe(18)
        ->and($lote->producto->fresh()?->existencia)->toBe(23)
        ->and(MovimientoProducto::query()->count())->toBe(1); // solo la producción del cierre
});

it('lista los ajustes del lote con su contexto', function () {
    $admin = adminDeAjustes();
    $lote = loteParaAjustes($admin);
    $asignado = $lote->insumos()->firstOrFail();

    postJson("/api/lotes/{$lote->codigo}/ajustes", [
        'lote_insumo_id' => $asignado->id,
        'campo' => 'cantidad',
        'valor' => 210,
        'motivo' => 'Pesaje corregido',
    ])->assertCreated();

    recorrerParaAjustes($lote, $admin, 1, 18);
    $parada = $lote->paradas()->firstOrFail();

    postJson("/api/lotes/{$lote->codigo}/ajustes", [
        'lote_area_id' => $parada->id,
        'campo' => 'piezas_entregadas',
        'valor' => 15,
        'motivo' => 'Piezas rotas',
    ])->assertCreated();

    getJson("/api/lotes/{$lote->codigo}/ajustes")
        ->assertOk()
        ->assertJsonCount(2)
        ->assertJsonPath('0.parada.area.codigo', 'MOL')
        ->assertJsonPath('0.campo', 'piezas_entregadas')
        ->assertJsonPath('0.usuario.name', $admin->name)
        ->assertJsonPath('1.insumo.codigo', 'CHT-CIE')
        ->assertJsonPath('1.campo', 'cantidad')
        ->assertJsonPath('1.motivo', 'Pesaje corregido');
});

it('solo lista los ajustes al administrador', function (Rol $rol) {
    $admin = adminDeAjustes();
    $lote = loteParaAjustes($admin);

    Sanctum::actingAs(User::factory()->create()->assignRole($rol));

    getJson("/api/lotes/{$lote->codigo}/ajustes")->assertForbidden();
})->with([Rol::Ventas, Rol::Encargado]);
