<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

use function Pest\Laravel\postJson;

it('envía el enlace de recuperación a un correo existente', function () {
    Notification::fake();
    $user = User::factory()->create();

    postJson('/forgot-password', ['email' => $user->email])
        ->assertOk()
        ->assertJsonPath('message', __('passwords.sent'));

    Notification::assertSentTo($user, ResetPassword::class);
});

it('responde igual con un correo que no existe y no revela que no existe', function () {
    Notification::fake();

    postJson('/forgot-password', ['email' => 'nadie@ejemplo.com'])
        ->assertOk()
        ->assertJsonPath('message', __('passwords.sent'));

    Notification::assertNothingSent();
});

it('restablece la contraseña con un token válido', function () {
    $user = User::factory()->create();
    $token = Password::createToken($user);

    postJson('/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'NuevaClave123',
        'password_confirmation' => 'NuevaClave123',
    ])
        ->assertOk()
        ->assertJsonPath('message', __('passwords.reset'));

    expect(Hash::check('NuevaClave123', $user->fresh()->password))->toBeTrue();
});

it('el token solo sirve una vez', function () {
    $user = User::factory()->create();
    $token = Password::createToken($user);

    $datos = [
        'token' => $token,
        'email' => $user->email,
        'password' => 'NuevaClave123',
        'password_confirmation' => 'NuevaClave123',
    ];

    postJson('/reset-password', $datos)->assertOk();
    postJson('/reset-password', $datos)->assertUnprocessable()->assertJsonValidationErrors('email');
});

it('rechaza un token inválido o caducado', function () {
    $user = User::factory()->create();

    postJson('/reset-password', [
        'token' => 'token-invalido',
        'email' => $user->email,
        'password' => 'NuevaClave123',
        'password_confirmation' => 'NuevaClave123',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('email');
});

it('rechaza contraseñas que no cumplen la política, con mensaje en español', function () {
    $user = User::factory()->create();
    $token = Password::createToken($user);

    $respuesta = postJson('/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'corta',
        'password_confirmation' => 'corta',
    ])->assertUnprocessable()->assertJsonValidationErrors('password');

    $mensajes = $respuesta->json('errors.password');
    expect(implode(' ', $mensajes))->toContain('contraseña');
});

it('rechaza cuando la confirmación no coincide', function () {
    $user = User::factory()->create();
    $token = Password::createToken($user);

    postJson('/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'NuevaClave123',
        'password_confirmation' => 'Otra123',
    ])->assertUnprocessable()->assertJsonValidationErrors('password');
});

it('limita los intentos de recuperación como el login', function () {
    Notification::fake();
    $user = User::factory()->create();

    foreach (range(1, 5) as $intento) {
        postJson('/forgot-password', ['email' => $user->email])->assertOk();
    }

    postJson('/forgot-password', ['email' => $user->email])->assertStatus(429);
});
