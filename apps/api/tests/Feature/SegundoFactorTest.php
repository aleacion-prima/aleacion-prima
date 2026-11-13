<?php

use App\Enums\Rol;
use App\Models\CodigoAcceso;
use App\Models\User;
use App\Notifications\CodigoAccesoCorreo;
use Database\Seeders\RolesSeeder;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\assertAuthenticatedAs;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;
use function Pest\Laravel\seed;

/**
 * Inicia el login de un usuario con segundo factor y devuelve el código de 6
 * dígitos que se envió por correo (capturado del Notification::fake).
 */
function iniciarConCodigo(User $usuario): string
{
    Notification::fake();

    postJson('/login', ['email' => $usuario->email, 'password' => 'password'])
        ->assertOk()
        ->assertExactJson(['segundo_factor' => true]);

    $codigo = '';
    Notification::assertSentTo(
        $usuario,
        CodigoAccesoCorreo::class,
        function (CodigoAccesoCorreo $notificacion) use (&$codigo): bool {
            $codigo = $notificacion->codigo;

            return true;
        },
    );

    return $codigo;
}

it('con segundo factor, el login deja la sesión pendiente y no entra a rutas protegidas', function () {
    $usuario = User::factory()->conSegundoFactor()->create();

    iniciarConCodigo($usuario);

    assertGuest();
    getJson('/api/user')->assertUnauthorized();
});

it('verifica el código correcto e inicia la sesión', function () {
    $usuario = User::factory()->conSegundoFactor()->create();
    $codigo = iniciarConCodigo($usuario);

    postJson('/login/verificar', ['codigo' => $codigo])->assertNoContent();

    assertAuthenticatedAs($usuario);
    getJson('/api/user')->assertOk();
});

it('un código solo sirve una vez', function () {
    $usuario = User::factory()->conSegundoFactor()->create();
    $codigo = iniciarConCodigo($usuario);

    postJson('/login/verificar', ['codigo' => $codigo])->assertNoContent();

    expect(CodigoAcceso::query()->where('user_id', $usuario->id)->exists())->toBeFalse();
});

it('rechaza un código vencido con 422', function () {
    $usuario = User::factory()->conSegundoFactor()->create();
    $codigo = iniciarConCodigo($usuario);

    CodigoAcceso::query()->where('user_id', $usuario->id)->update(['expira_en' => now()->subMinute()]);

    postJson('/login/verificar', ['codigo' => $codigo])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('codigo');

    assertGuest();
});

it('bloquea tras agotar los 5 intentos y pide un código nuevo', function () {
    $usuario = User::factory()->conSegundoFactor()->create();
    $codigo = iniciarConCodigo($usuario);
    $malo = $codigo === '000000' ? '111111' : '000000';

    foreach (range(1, 5) as $intento) {
        postJson('/login/verificar', ['codigo' => $malo])
            ->assertUnprocessable()
            ->assertJsonPath('errors.codigo.0', 'El código no es correcto.');
    }

    postJson('/login/verificar', ['codigo' => $malo])
        ->assertUnprocessable()
        ->assertJsonPath('errors.codigo.0', 'Agotaste los intentos. Pide un código nuevo.');

    assertGuest();
});

it('reenvía un código nuevo a la sesión pendiente', function () {
    $usuario = User::factory()->conSegundoFactor()->create();
    iniciarConCodigo($usuario);

    Notification::fake();
    postJson('/login/reenviar')->assertOk();

    Notification::assertSentTo($usuario, CodigoAccesoCorreo::class);
});

it('un usuario sin segundo factor entra directo, sin código', function () {
    Notification::fake();
    $usuario = User::factory()->create();

    postJson('/login', ['email' => $usuario->email, 'password' => 'password'])->assertNoContent();

    assertAuthenticatedAs($usuario);
    Notification::assertNothingSent();
});

it('un usuario activa y desactiva su segundo factor desde Mi cuenta', function () {
    seed(RolesSeeder::class);
    $usuario = User::factory()->create();
    $usuario->assignRole(Rol::Ventas);
    Sanctum::actingAs($usuario);

    putJson('/api/perfil/segundo-factor', ['activo' => true])
        ->assertOk()
        ->assertJsonPath('activo', true);
    expect($usuario->fresh()?->segundo_factor)->toBeTrue();

    putJson('/api/perfil/segundo-factor', ['activo' => false])
        ->assertOk()
        ->assertJsonPath('activo', false);
    expect($usuario->fresh()?->segundo_factor)->toBeFalse();
});

it('el administrador no puede desactivar su segundo factor', function () {
    seed(RolesSeeder::class);
    $admin = User::factory()->conSegundoFactor()->create();
    $admin->assignRole(Rol::Administrador);
    Sanctum::actingAs($admin);

    putJson('/api/perfil/segundo-factor', ['activo' => false])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('activo');

    expect($admin->fresh()?->segundo_factor)->toBeTrue();
});

it('muestra el estado del segundo factor y si es obligatorio', function () {
    seed(RolesSeeder::class);
    $admin = User::factory()->conSegundoFactor()->create();
    $admin->assignRole(Rol::Administrador);
    Sanctum::actingAs($admin);

    getJson('/api/perfil/segundo-factor')
        ->assertOk()
        ->assertExactJson(['activo' => true, 'obligatorio' => true]);
});
