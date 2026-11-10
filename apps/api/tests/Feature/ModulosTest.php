<?php

use App\Events\ModulosActualizados;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Laravel\Pennant\Feature;
use Laravel\Pennant\Middleware\EnsureFeaturesAreActive;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

it('los comandos prenden y apagan el módulo para todos', function () {
    expect(Feature::active('ventas'))->toBeFalse();

    $this->artisan('modulos:activar ventas')->assertSuccessful();
    expect(Feature::active('ventas'))->toBeTrue();

    $this->artisan('modulos:desactivar ventas')->assertSuccessful();
    expect(Feature::active('ventas'))->toBeFalse();
});

it('el comando rechaza un módulo desconocido', function () {
    $this->artisan('modulos:activar inventado')->assertFailed();
    $this->artisan('modulos:desactivar inventado')->assertFailed();
});

it('con el módulo apagado una ruta de ventas no responde, y prendido sí', function () {
    Route::middleware(['auth:sanctum', EnsureFeaturesAreActive::using('ventas')])
        ->get('/_test/ventas', fn () => response()->json(['ok' => true]));

    Sanctum::actingAs(User::factory()->create());

    getJson('/_test/ventas')->assertStatus(400);

    Feature::activate('ventas');
    getJson('/_test/ventas')->assertOk();
});

it('difunde ModulosActualizados al cambiar una bandera', function () {
    Event::fake([ModulosActualizados::class]);

    Feature::activate('ventas');

    Event::assertDispatched(
        ModulosActualizados::class,
        fn (ModulosActualizados $e) => ($e->modulos['ventas'] ?? null) === true,
    );
});

it('ModulosActualizados viaja por el canal privado modulos', function () {
    $evento = new ModulosActualizados(['ventas' => true]);

    expect($evento->broadcastOn()->name)->toBe('private-modulos');
    expect($evento->broadcastAs())->toBe('modulos.actualizados');
    expect($evento->broadcastWith())->toBe(['modulos' => ['ventas' => true]]);
});

it('/api/user incluye el estado de los módulos', function () {
    Sanctum::actingAs(User::factory()->create());

    getJson('/api/user')->assertOk()->assertJsonPath('modulos.ventas', false);

    Feature::activate('ventas');
    getJson('/api/user')->assertJsonPath('modulos.ventas', true);
});

it('el canal de módulos rechaza a quien no tiene sesión', function () {
    // El endpoint de broadcasting pasa por 'auth:sanctum', así que un invitado
    // se rechaza con 401 antes de llegar a la autorización del canal.
    postJson('/broadcasting/auth', [
        'channel_name' => 'private-modulos',
        'socket_id' => '1.1',
    ])->assertUnauthorized();

    Sanctum::actingAs(User::factory()->create());
    postJson('/broadcasting/auth', [
        'channel_name' => 'private-modulos',
        'socket_id' => '1.1',
    ])->assertOk();
});
