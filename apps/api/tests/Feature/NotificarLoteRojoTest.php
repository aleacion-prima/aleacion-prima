<?php

use App\Enums\Rol;
use App\Enums\Semaforo;
use App\Models\Area;
use App\Models\Lote;
use App\Models\LoteArea;
use App\Models\Producto;
use App\Models\User;
use App\Notifications\LotePasoARojo;
use Database\Seeders\AreaSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\postJson;
use function Pest\Laravel\seed;
use function Pest\Laravel\travelTo;

beforeEach(function () {
    seed([RolesSeeder::class, AreaSeeder::class]);
    // Hora fija temprana: las capturas ocurren minutos antes, así no salen
    // tardías (y no agregan un ámbar que enturbie los motivos del semáforo).
    travelTo('2026-11-02 06:20:00');
});

function adminRojo(bool $activo = true, string $nombre = 'Admin Rojo'): User
{
    $admin = User::factory()->create(['activo' => $activo, 'name' => $nombre]);
    $admin->assignRole(Rol::Administrador);

    return $admin;
}

/**
 * Lote en proceso, con paradas sin capturar listas para recibir entrada. El precio
 * de mayoreo alto deja la señal de costo apagada salvo donde se busca a propósito.
 *
 * @param  list<array{area: string, tarifa?: int, maquina?: int}>  $paradas
 */
function loteRojo(array $paradas = [['area' => 'MOL']], int $objetivo = 100, string $precio = '100000'): Lote
{
    $producto = Producto::factory()->create(['precio_mayoreo' => $precio]);
    $lote = Lote::factory()->enProceso()->create([
        'producto_id' => $producto->id,
        'cantidad_objetivo' => $objetivo,
        'costo_total' => '0',
    ]);

    foreach ($paradas as $indice => $datos) {
        LoteArea::factory()->create([
            'lote_id' => $lote->id,
            'area_id' => Area::query()->where('codigo', $datos['area'])->value('id'),
            'orden' => $indice + 1,
            'tarifa_mano_obra_hora' => $datos['tarifa'] ?? 1,
            'costo_maquina_hora' => $datos['maquina'] ?? 1,
        ]);
    }

    return $lote;
}

function paradaRojo(Lote $lote, int $orden): LoteArea
{
    return $lote->paradas()->where('orden', $orden)->firstOrFail();
}

function entrarRojo(Lote $lote, int $orden, int $piezas, string $hora): void
{
    postJson("/api/lotes/{$lote->codigo}/paradas/".paradaRojo($lote, $orden)->id.'/entrada', [
        'piezas_recibidas' => $piezas,
        'ocurrido_en' => "2026-11-02 {$hora}",
    ])->assertOk();
}

function salirRojo(Lote $lote, int $orden, int $piezas, string $hora): void
{
    postJson("/api/lotes/{$lote->codigo}/paradas/".paradaRojo($lote, $orden)->id.'/salida', [
        'piezas_entregadas' => $piezas,
        'ocurrido_en' => "2026-11-02 {$hora}",
    ])->assertOk();
}

function ajustarRojo(Lote $lote, int $orden, string $campo, mixed $valor): void
{
    postJson("/api/lotes/{$lote->codigo}/ajustes", [
        'lote_area_id' => paradaRojo($lote, $orden)->id,
        'campo' => $campo,
        'valor' => $valor,
        'motivo' => 'Ajuste para la prueba del semáforo',
    ])->assertCreated();
}

it('notifica a cada administrador activo cuando un lote pasa a rojo por merma', function () {
    Notification::fake();
    $actor = adminRojo(nombre: 'Ana Actor');
    $otro = adminRojo(nombre: 'Beto Otro');
    Sanctum::actingAs($actor, ['*']);

    $lote = loteRojo();
    entrarRojo($lote, 1, 100, '06:00');
    salirRojo($lote, 1, 85, '06:05'); // merma 15 de 100 (15 %) → rojo

    foreach ([$actor, $otro] as $admin) {
        Notification::assertSentTo($admin, LotePasoARojo::class);
    }

    Notification::assertSentTo($actor, LotePasoARojo::class, function (LotePasoARojo $noti) use ($actor, $lote): bool {
        $mensaje = $noti->toArray($actor)['mensaje'];

        return str_contains($mensaje, "El lote {$lote->codigo} pasó a rojo")
            && str_contains($mensaje, 'Merma acumulada');
    });

    expect($lote->refresh()->semaforo_notificado)->toBe(Semaforo::Rojo);
});

it('notifica cuando un lote pasa a rojo por un cambio de costo', function () {
    Notification::fake();
    $actor = adminRojo();
    Sanctum::actingAs($actor, ['*']);

    // Precio bajo y dos paradas: la primera salida deja el costo en verde y el lote
    // en proceso; el ajuste alarga la salida hasta cruzar el 100 % del precio.
    $lote = loteRojo([['area' => 'MOL', 'tarifa' => 100, 'maquina' => 0], ['area' => 'FUS']], precio: '10');
    entrarRojo($lote, 1, 100, '06:00');
    salirRojo($lote, 1, 100, '06:10'); // sin merma; 10 min a $100/h → verde

    Notification::assertNotSentTo($actor, LotePasoARojo::class);

    // 10 h a $100/h = $1000 = 100 % de 10 × 100 piezas → rojo por costo.
    ajustarRojo($lote, 1, 'salida_ocurrido_en', '2026-11-02 16:00:00');

    Notification::assertSentTo($actor, LotePasoARojo::class, function (LotePasoARojo $noti) use ($actor): bool {
        return str_contains($noti->toArray($actor)['mensaje'], 'costo por pieza');
    });
});

it('no repite la notificación mientras el lote siga en rojo', function () {
    Notification::fake();
    $actor = adminRojo();
    Sanctum::actingAs($actor, ['*']);

    $lote = loteRojo([['area' => 'MOL'], ['area' => 'FUS']]);
    entrarRojo($lote, 1, 100, '06:00');
    salirRojo($lote, 1, 85, '06:05'); // merma 15 → rojo (notifica)

    entrarRojo($lote, 2, 85, '06:08');
    salirRojo($lote, 2, 80, '06:12'); // merma total 20 → sigue en rojo (no repite)

    Notification::assertSentToTimes($actor, LotePasoARojo::class, 1);
});

it('vuelve a notificar si el lote sale de rojo y regresa', function () {
    Notification::fake();
    $actor = adminRojo();
    Sanctum::actingAs($actor, ['*']);

    $lote = loteRojo([['area' => 'MOL'], ['area' => 'FUS']]);
    entrarRojo($lote, 1, 100, '06:00');
    salirRojo($lote, 1, 85, '06:05'); // merma 15 → rojo (notifica 1)

    ajustarRojo($lote, 1, 'piezas_entregadas', 98); // merma 2 → verde (guarda verde)
    ajustarRojo($lote, 1, 'piezas_entregadas', 85); // merma 15 → rojo otra vez (notifica 2)

    Notification::assertSentToTimes($actor, LotePasoARojo::class, 2);
});

it('no notifica a administradores inactivos ni a quien no es administrador', function () {
    Notification::fake();
    $actor = adminRojo(nombre: 'Ana Activa');
    $inactivo = adminRojo(activo: false, nombre: 'Iván Inactivo');
    $ventas = User::factory()->create();
    $ventas->assignRole(Rol::Ventas);
    Sanctum::actingAs($actor, ['*']);

    $lote = loteRojo();
    entrarRojo($lote, 1, 100, '06:00');
    salirRojo($lote, 1, 85, '06:05'); // merma 15 → rojo

    Notification::assertSentTo($actor, LotePasoARojo::class);
    Notification::assertNotSentTo([$inactivo, $ventas], LotePasoARojo::class);
});

it('el ámbar no dispara la notificación de rojo', function () {
    Notification::fake();
    $actor = adminRojo();
    Sanctum::actingAs($actor, ['*']);

    $lote = loteRojo();
    entrarRojo($lote, 1, 100, '06:00');
    salirRojo($lote, 1, 95, '06:05'); // merma 5 de 100 (5 %) → ámbar

    Notification::assertNotSentTo($actor, LotePasoARojo::class);
    expect($lote->refresh()->semaforo_notificado)->toBe(Semaforo::Ambar);
});
