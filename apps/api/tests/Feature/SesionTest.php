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
        ->assertJsonValidationErrors('email')
        ->assertJsonPath('errors.email.0', 'Estas credenciales no coinciden con nuestros registros.');

    assertGuest();
});

it('rechaza a un usuario inactivo', function () {
    $user = User::factory()->create(['activo' => false]);

    postJson('/login', ['email' => $user->email, 'password' => 'password'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('email');

    assertGuest();
});

it('cierra la sesión', function () {
    $user = User::factory()->create();

    postJson('/login', ['email' => $user->email, 'password' => 'password']);
    postJson('/logout')->assertNoContent();

    assertGuest();
});
