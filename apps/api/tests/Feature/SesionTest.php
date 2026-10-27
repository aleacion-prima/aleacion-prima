<?php

use App\Enums\Rol;
use App\Models\User;
use Database\Seeders\RolesSeeder;

use function Pest\Laravel\assertAuthenticatedAs;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\seed;

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
        ->assertExactJson(['id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'rol' => 'ventas']);
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
