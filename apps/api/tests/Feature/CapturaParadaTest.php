<?php

use App\Broadcasting\CanalTablero;
use App\Enums\EstadoLote;
use App\Enums\Rol;
use App\Enums\TipoMovimientoInsumo;
use App\Events\LoteActualizado;
use App\Lotes\CreadorLote;
use App\Models\Area;
use App\Models\Insumo;
use App\Models\Lote;
use App\Models\LoteArea;
use App\Models\MovimientoInsumo;
use App\Models\Producto;
use App\Models\User;
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
    travelTo('2026-11-02 08:00:00');
});

function capturarComo(Rol $rol, array $areas = []): User
{
    $usuario = User::factory()->create();
    $usuario->assignRole($rol);
    $usuario->areas()->sync(Area::query()->whereIn('codigo', $areas)->pluck('id'));
    Sanctum::actingAs($usuario);

    return $usuario;
}

function loteParaCaptura(): Lote
{
    $producto = Producto::factory()->create();

    foreach (['MOL', 'FUS', 'COL', 'DES'] as $indice => $codigo) {
        $producto->areas()->attach(Area::query()->where('codigo', $codigo)->value('id'), ['orden' => $indice + 1]);
    }

    $chatarra = Insumo::query()->where('codigo', config('planta.insumo_retorno'))->first() ?? Insumo::factory()->create([
        'codigo' => config('planta.insumo_retorno'),
        'categoria' => 'metal',
        'unidad' => 'kg',
        'costo_unitario' => 7,
        'existencia' => 1000,
    ]);
    $producto->insumos()->attach($chatarra->id, ['cantidad_por_pieza' => 10]);

    $creador = User::factory()->create();
    $creador->assignRole(Rol::Administrador);

    return app(CreadorLote::class)->crear($producto, 20, null, $creador);
}

function paradaDe(Lote $lote, int $orden): LoteArea
{
    return $lote->paradas()->where('orden', $orden)->firstOrFail();
}

function urlCaptura(Lote $lote, LoteArea $parada, string $accion): string
{
    return "/api/lotes/{$lote->codigo}/paradas/{$parada->id}/{$accion}";
}

function entrar(Lote $lote, int $orden, int $piezas, string $hora): void
{
    postJson(urlCaptura($lote, paradaDe($lote, $orden), 'entrada'), [
        'piezas_recibidas' => $piezas,
        'ocurrido_en' => "2026-11-02 {$hora}",
    ])->assertOk();
}

function salir(Lote $lote, int $orden, int $piezas, string $hora, array $extra = []): void
{
    postJson(urlCaptura($lote, paradaDe($lote, $orden), 'salida'), [
        'piezas_entregadas' => $piezas,
        'ocurrido_en' => "2026-11-02 {$hora}",
        ...$extra,
    ])->assertOk();
}

it('registra la entrada a la primera parada y pone el lote en proceso', function () {
    $admin = capturarComo(Rol::Administrador);
    $lote = loteParaCaptura();

    postJson(urlCaptura($lote, paradaDe($lote, 1), 'entrada'), [
        'piezas_recibidas' => 20,
        'ocurrido_en' => '2026-11-02 07:00',
    ])
        ->assertOk()
        ->assertJsonPath('estado', 'en_proceso')
        ->assertJsonPath('area_actual.codigo', 'MOL')
        ->assertJsonPath('paradas.0.piezas_recibidas', 20);

    $parada = paradaDe($lote, 1);
    expect($parada->entrada_user_id)->toBe($admin->id)
        ->and($parada->entrada_ocurrido_en?->format('H:i'))->toBe('07:00')
        ->and($parada->entrada_registrado_en?->format('H:i'))->toBe('08:00');
});

it('registra la salida, calcula la merma y deja el lote en tránsito', function () {
    capturarComo(Rol::Administrador);
    $lote = loteParaCaptura();
    entrar($lote, 1, 20, '07:00');

    postJson(urlCaptura($lote, paradaDe($lote, 1), 'salida'), [
        'piezas_entregadas' => 18,
        'merma_material' => 1.5,
        'ocurrido_en' => '2026-11-02 07:45',
    ])
        ->assertOk()
        ->assertJsonPath('estado', 'en_proceso')
        ->assertJsonPath('area_actual', null)
        ->assertJsonPath('paradas.0.merma_piezas', 2)
        ->assertJsonPath('paradas.0.merma_material', '1.500');
});

it('exige el orden del recorrido', function () {
    capturarComo(Rol::Administrador);
    $lote = loteParaCaptura();

    postJson(urlCaptura($lote, paradaDe($lote, 2), 'entrada'), ['piezas_recibidas' => 20])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['parada']);

    postJson(urlCaptura($lote, paradaDe($lote, 1), 'salida'), ['piezas_entregadas' => 20])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['parada']);
});

it('no permite capturar dos veces la misma entrada o salida', function () {
    capturarComo(Rol::Administrador);
    $lote = loteParaCaptura();
    entrar($lote, 1, 20, '07:00');

    postJson(urlCaptura($lote, paradaDe($lote, 1), 'entrada'), ['piezas_recibidas' => 20])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['parada']);

    salir($lote, 1, 20, '07:30');

    postJson(urlCaptura($lote, paradaDe($lote, 1), 'salida'), ['piezas_entregadas' => 20])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['parada']);
});

it('no entrega más piezas de las que recibió', function () {
    capturarComo(Rol::Administrador);
    $lote = loteParaCaptura();
    entrar($lote, 1, 20, '07:00');

    postJson(urlCaptura($lote, paradaDe($lote, 1), 'salida'), ['piezas_entregadas' => 21])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['piezas_entregadas']);
});

it('valida que las horas tengan sentido', function () {
    capturarComo(Rol::Administrador);
    $lote = loteParaCaptura();

    postJson(urlCaptura($lote, paradaDe($lote, 1), 'entrada'), [
        'piezas_recibidas' => 20,
        'ocurrido_en' => '2026-11-02 09:00',
    ])->assertUnprocessable()->assertJsonValidationErrors(['ocurrido_en']);

    entrar($lote, 1, 20, '07:00');

    postJson(urlCaptura($lote, paradaDe($lote, 1), 'salida'), [
        'piezas_entregadas' => 20,
        'ocurrido_en' => '2026-11-02 06:30',
    ])->assertUnprocessable()->assertJsonValidationErrors(['ocurrido_en']);

    salir($lote, 1, 20, '07:30');

    postJson(urlCaptura($lote, paradaDe($lote, 2), 'entrada'), [
        'piezas_recibidas' => 20,
        'ocurrido_en' => '2026-11-02 07:15',
    ])->assertUnprocessable()->assertJsonValidationErrors(['ocurrido_en']);

    entrar($lote, 2, 20, '07:30');
});

it('marca la discrepancia y la captura tardía', function () {
    capturarComo(Rol::Administrador);
    $lote = loteParaCaptura();
    entrar($lote, 1, 20, '06:00');
    salir($lote, 1, 19, '07:50');
    entrar($lote, 2, 17, '07:55');

    getJson("/api/lotes/{$lote->codigo}")
        ->assertJsonPath('paradas.0.entrada_tardia', true)
        ->assertJsonPath('paradas.0.salida_tardia', false)
        ->assertJsonPath('paradas.0.discrepancia', null)
        ->assertJsonPath('paradas.1.discrepancia', -2);
});

it('regresa el metal de desmolde al inventario', function () {
    capturarComo(Rol::Administrador);
    $lote = loteParaCaptura();
    $chatarra = Insumo::query()->where('codigo', config('planta.insumo_retorno'))->firstOrFail();
    expect($chatarra->existencia)->toBe('800.000');

    entrar($lote, 1, 20, '06:00');
    salir($lote, 1, 20, '06:10');
    entrar($lote, 2, 20, '06:10');
    salir($lote, 2, 20, '06:20');
    entrar($lote, 3, 20, '06:20');
    salir($lote, 3, 20, '06:30');
    entrar($lote, 4, 20, '06:30');
    salir($lote, 4, 19, '06:40', ['retorno_kg' => 35.5]);

    expect($chatarra->fresh()?->existencia)->toBe('835.500');

    $retorno = MovimientoInsumo::query()->where('tipo', TipoMovimientoInsumo::Retorno)->firstOrFail();
    expect($retorno->cantidad)->toBe('35.500')
        ->and($retorno->lote_id)->toBe($lote->id)
        ->and($retorno->costo_unitario)->toBe('7.0000');
});

it('solo acepta retorno en desmolde y hasta el metal asignado', function () {
    capturarComo(Rol::Administrador);
    $lote = loteParaCaptura();
    entrar($lote, 1, 20, '06:00');

    postJson(urlCaptura($lote, paradaDe($lote, 1), 'salida'), [
        'piezas_entregadas' => 20,
        'retorno_kg' => 10,
    ])->assertUnprocessable()->assertJsonValidationErrors(['retorno_kg']);

    salir($lote, 1, 20, '06:10');
    entrar($lote, 2, 20, '06:10');
    salir($lote, 2, 20, '06:20');
    entrar($lote, 3, 20, '06:20');
    salir($lote, 3, 20, '06:30');
    entrar($lote, 4, 20, '06:30');

    postJson(urlCaptura($lote, paradaDe($lote, 4), 'salida'), [
        'piezas_entregadas' => 20,
        'retorno_kg' => 200.001,
    ])->assertUnprocessable()->assertJsonValidationErrors(['retorno_kg']);
});

it('pasa el lote a por confirmar al salir de la última parada', function () {
    capturarComo(Rol::Administrador);
    $lote = loteParaCaptura();

    foreach ([1, 2, 3, 4] as $orden) {
        entrar($lote, $orden, 20, sprintf('06:%02d', $orden * 10));
        salir($lote, $orden, 20, sprintf('06:%02d', $orden * 10 + 5));
    }

    expect($lote->fresh()?->estado)->toBe(EstadoLote::PorConfirmar)
        ->and($lote->fresh()?->area_actual_id)->toBeNull();

    postJson(urlCaptura($lote, paradaDe($lote, 1), 'entrada'), ['piezas_recibidas' => 20])
        ->assertUnprocessable();
});

it('deja capturar al encargado solo en sus áreas', function () {
    $lote = loteParaCaptura();
    capturarComo(Rol::Encargado, ['MOL']);

    entrar($lote, 1, 20, '07:00');

    getJson("/api/lotes/{$lote->codigo}")
        ->assertJsonPath('paradas.0.puede_capturar', true)
        ->assertJsonPath('paradas.1.puede_capturar', false);

    salir($lote, 1, 20, '07:30');

    postJson(urlCaptura($lote, paradaDe($lote, 2), 'entrada'), ['piezas_recibidas' => 20])
        ->assertForbidden();
});

it('rechaza a ventas y paradas de otro lote', function () {
    $lote = loteParaCaptura();
    $otro = loteParaCaptura();

    capturarComo(Rol::Ventas);
    postJson(urlCaptura($lote, paradaDe($lote, 1), 'entrada'), ['piezas_recibidas' => 20])
        ->assertForbidden();

    capturarComo(Rol::Administrador);
    postJson(urlCaptura($lote, paradaDe($otro, 1), 'entrada'), ['piezas_recibidas' => 20])
        ->assertNotFound();
});

it('avisa al tablero en cada cambio del lote', function () {
    Event::fake([LoteActualizado::class]);
    capturarComo(Rol::Administrador);

    $lote = loteParaCaptura();
    Event::assertDispatchedTimes(LoteActualizado::class, 1);

    entrar($lote, 1, 20, '07:00');
    salir($lote, 1, 20, '07:30');

    Event::assertDispatchedTimes(LoteActualizado::class, 3);
    Event::assertDispatched(
        LoteActualizado::class,
        fn (LoteActualizado $evento) => $evento->broadcastWith() === [
            'codigo' => $lote->codigo,
            'estado' => 'en_proceso',
            'area_actual_id' => null,
        ],
    );
});

it('solo deja escuchar el tablero al administrador', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Rol::Administrador);
    $ventas = User::factory()->create();
    $ventas->assignRole(Rol::Ventas);

    $canal = new CanalTablero;

    expect($canal->join($admin))->toBeTrue()
        ->and($canal->join($ventas))->toBeFalse();
});
