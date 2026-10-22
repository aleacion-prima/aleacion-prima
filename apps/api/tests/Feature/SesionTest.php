<?php

use App\Models\User;

use function Pest\Laravel\assertAuthenticatedAs;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\postJson;

it('inicia sesión con credenciales válidas', function () {
    $user = User::factory()->create();

    postJson('/login', ['email' => $user->email, 'password' => 'password'])
        ->assertNoContent();

    assertAuthenticatedAs($user);
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
