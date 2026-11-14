<?php

use App\Enums\EstadoLote;
use App\Enums\Rol;
use App\Events\LoteActualizado;
use App\Events\StockMinimoAlcanzado;
use App\Models\Insumo;
use App\Models\Lote;
use App\Models\User;
use App\Notifications\ImprevistoRegistrado;
use App\Notifications\LotePorConfirmar;
use App\Notifications\StockMinimoBajo;
use Database\Seeders\RolesSeeder;
use Illuminate\Contracts\Broadcasting\Broadcaster;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;
use function Pest\Laravel\patchJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\seed;

beforeEach(function () {
    seed(RolesSeeder::class);
});

function admin(bool $activo = true): User
{
    $usuario = User::factory()->create(['activo' => $activo]);
    $usuario->assignRole(Rol::Administrador);

    return $usuario;
}

it('avisa a los administradores cuando un insumo llega al stock mínimo', function () {
    Notification::fake();
    $admin = admin();
    $insumo = Insumo::factory()->create(['stock_minimo' => 5, 'existencia' => 0]);

    event(new StockMinimoAlcanzado($insumo->id, 0, 5));

    Notification::assertSentTo($admin, StockMinimoBajo::class);
});

it('avisa cuando un lote pasa a por confirmar', function () {
    Notification::fake();
    $admin = admin();
    $lote = Lote::factory()->create(['estado' => EstadoLote::PorConfirmar]);

    event(new LoteActualizado($lote));

    Notification::assertSentTo($admin, LotePorConfirmar::class);
});

it('no avisa por confirmar en otros estados del lote', function () {
    Notification::fake();
    admin();
    $lote = Lote::factory()->create(['estado' => EstadoLote::EnProceso]);

    event(new LoteActualizado($lote));

    Notification::assertNothingSent();
});

it('puede enviar la notificación de imprevisto a un administrador', function () {
    // La dispara T-43 cuando se registre un imprevisto; aquí solo se comprueba
    // que la notificación está lista para usarse.
    Notification::fake();
    $admin = admin();

    $admin->notify(new ImprevistoRegistrado('LOT-20260101-01', 'Falla de máquina en Fundición'));

    Notification::assertSentTo($admin, ImprevistoRegistrado::class, function (ImprevistoRegistrado $notificacion) use ($admin) {
        return $notificacion->toArray($admin)['tipo'] === 'imprevisto';
    });
});

it('lista las notificaciones del administrador, paginadas y filtrando no leídas', function () {
    $admin = admin();
    $insumo = Insumo::factory()->create();
    $admin->notify(new StockMinimoBajo($insumo));
    $admin->notify(new StockMinimoBajo($insumo));
    $admin->unreadNotifications->first()?->markAsRead();

    Sanctum::actingAs($admin, ['*']);

    getJson('/api/notificaciones')->assertOk()->assertJsonCount(2, 'data');
    getJson('/api/notificaciones?no_leidas=1')->assertOk()->assertJsonCount(1, 'data');
});

it('marca una notificación como leída', function () {
    $admin = admin();
    $insumo = Insumo::factory()->create();
    $admin->notify(new StockMinimoBajo($insumo));
    $id = $admin->notifications()->firstOrFail()->id;

    Sanctum::actingAs($admin, ['*']);

    patchJson("/api/notificaciones/{$id}/leida")->assertNoContent();

    expect($admin->notifications()->firstOrFail()->read_at)->not->toBeNull();
});

it('marca todas las notificaciones como leídas', function () {
    $admin = admin();
    $insumo = Insumo::factory()->create();
    $admin->notify(new StockMinimoBajo($insumo));
    $admin->notify(new StockMinimoBajo($insumo));

    Sanctum::actingAs($admin, ['*']);

    postJson('/api/notificaciones/leidas')->assertNoContent();

    expect($admin->unreadNotifications()->count())->toBe(0);
});

it('no deja marcar como leída una notificación ajena', function () {
    $a = admin();
    $b = admin();
    $insumo = Insumo::factory()->create();
    $b->notify(new StockMinimoBajo($insumo));
    $idDeB = $b->notifications()->firstOrFail()->id;

    Sanctum::actingAs($a, ['*']);

    patchJson("/api/notificaciones/{$idDeB}/leida")->assertNotFound();

    expect($b->notifications()->firstOrFail()->read_at)->toBeNull();
});

it('el canal privado del usuario solo autoriza a su dueño', function () {
    $usuario = User::factory()->create();
    $otro = User::factory()->create();

    // En pruebas el broadcaster es null y /broadcasting/auth no deniega, así que
    // se prueba directamente la autorización de routes/channels.php.
    $broadcaster = app(Broadcaster::class);
    $canales = (new ReflectionProperty($broadcaster, 'channels'))->getValue($broadcaster);
    $autorizar = $canales['App.Models.User.{id}'];

    expect($autorizar($usuario, (string) $usuario->id))->toBeTrue();
    expect($autorizar($otro, (string) $usuario->id))->toBeFalse();
});
