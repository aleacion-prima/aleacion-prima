<?php

use App\Enums\Rol;
use App\Models\User;
use Database\Seeders\RolesSeeder;

use function Pest\Laravel\assertAuthenticatedAs;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\seed;
use function Pest\Laravel\withHeaders;

it('inicia sesión con credenciales válidas', function () {
    $user = User::factory()->create();

    postJson('/login', ['email' => $user->email, 'password' => 'password'])
        ->assertNoContent();

    assertAuthenticatedAs($user);
});

it('devuelve el usuario con su rol y sin contraseña', function () {
    seed(RolesSeeder::class);
    $user = User::factory()->create();
    $user->assignRole(Rol::Ventas);

    postJson('/login', ['email' => $user->email, 'password' => 'password']);

    getJson('/api/user')
        ->assertOk()
        ->assertExactJson([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'rol' => 'ventas',
            'activo' => true,
            'modulos' => ['ventas' => false],
            'areas' => [],
        ]);
});

it('no inicia sesión con credenciales inválidas', function () {
    $user = User::factory()->create();

    postJson('/login', ['email' => $user->email, 'password' => 'otra'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('credenciales')
        ->assertJsonPath('errors.credenciales.0', 'Correo o contraseña incorrectos.');

    assertGuest();
});

it('rechaza a un usuario inactivo', function () {
    $user = User::factory()->create(['activo' => false]);

    postJson('/login', ['email' => $user->email, 'password' => 'password'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('credenciales');

    assertGuest();
});

it('cierra la sesión', function () {
    $user = User::factory()->create();

    postJson('/login', ['email' => $user->email, 'password' => 'password']);
    postJson('/logout')->assertNoContent();

    assertGuest();
});

it('bloquea el sexto intento fallido en un minuto', function () {
    $user = User::factory()->create();

    foreach (range(1, 5) as $intento) {
        postJson('/login', ['email' => $user->email, 'password' => 'otra'])->assertUnprocessable();
    }

    postJson('/login', ['email' => $user->email, 'password' => 'password'])
        ->assertStatus(429)
        ->assertJsonPath('message', fn (string $m) => str_starts_with($m, 'Demasiados intentos'));

    assertGuest();
});

it('cierra la sesión de un usuario desactivado', function () {
    $user = User::factory()->create();

    postJson('/login', ['email' => $user->email, 'password' => 'password'])->assertNoContent();
    $user->forceFill(['activo' => false])->save();
    app('auth')->forgetGuards();

    getJson('/api/user')
        ->assertUnauthorized()
        ->assertJsonPath('message', 'Tu cuenta está desactivada.');

    app('auth')->forgetGuards();
    getJson('/api/user')->assertUnauthorized();
});

it('cambia de usuario si ya hay una sesión activa en el navegador', function () {
    $encargado = User::factory()->create();
    $administrador = User::factory()->create();

    postJson('/login', ['email' => $encargado->email, 'password' => 'password'])->assertNoContent();
    postJson('/login', ['email' => $administrador->email, 'password' => 'password'])->assertNoContent();

    assertAuthenticatedAs($administrador);
});

it('detrás del proxy cuenta los intentos por la IP real del cliente', function () {
    $user = User::factory()->create();
    $desde = fn (string $ip) => withHeaders(['X-Forwarded-For' => $ip]);

    foreach (range(1, 5) as $intento) {
        $desde('203.0.113.10')
            ->postJson('/login', ['email' => $user->email, 'password' => 'otra'])
            ->assertUnprocessable();
    }

    $desde('203.0.113.10')
        ->postJson('/login', ['email' => $user->email, 'password' => 'password'])
        ->assertTooManyRequests();

    $desde('198.51.100.20')
        ->postJson('/login', ['email' => $user->email, 'password' => 'password'])
        ->assertNoContent();
});
