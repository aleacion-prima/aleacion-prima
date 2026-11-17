<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

/** @return string Primera pregunta de la lista de configuración. */
function preguntaValida(): string
{
    $preguntas = (array) config('preguntas-secretas.preguntas');

    return (string) $preguntas[0];
}

it('el usuario guarda su pregunta y la respuesta queda hasheada, no en texto plano', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['*']);

    putJson('/api/perfil/pregunta-secreta', [
        'pregunta' => preguntaValida(),
        'respuesta' => 'Mi Perro  Firulais',
    ])->assertOk();

    $user->refresh();

    expect($user->pregunta_secreta)->toBe(preguntaValida());
    expect($user->respuesta_secreta)->not->toBe('Mi Perro  Firulais');
    expect(Hash::check('mi perro firulais', $user->respuesta_secreta))->toBeTrue();
});

it('rechaza una pregunta que no está en la lista', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['*']);

    putJson('/api/perfil/pregunta-secreta', [
        'pregunta' => 'Una pregunta inventada',
        'respuesta' => 'algo',
    ])->assertUnprocessable()->assertJsonValidationErrors('pregunta');
});

it('muestra la pregunta actual y la lista disponible', function () {
    $user = User::factory()->create(['pregunta_secreta' => preguntaValida()]);
    Sanctum::actingAs($user, ['*']);

    getJson('/api/perfil/pregunta-secreta')
        ->assertOk()
        ->assertJsonPath('pregunta', preguntaValida())
        ->assertJsonCount(count((array) config('preguntas-secretas.preguntas')), 'preguntas');
});

it('un invitado no puede ver la pregunta', function () {
    getJson('/api/perfil/pregunta-secreta')->assertUnauthorized();
});

it('devuelve la pregunta del usuario al escribir su correo', function () {
    $user = User::factory()->create(['pregunta_secreta' => preguntaValida()]);

    postJson('/forgot-password/pregunta', ['email' => $user->email])
        ->assertOk()
        ->assertJsonPath('pregunta', preguntaValida());
});

it('devuelve una pregunta falsa pero estable para un correo inexistente (no revela)', function () {
    $preguntas = (array) config('preguntas-secretas.preguntas');

    $r1 = postJson('/forgot-password/pregunta', ['email' => 'nadie@ejemplo.com'])->assertOk();
    $r2 = postJson('/forgot-password/pregunta', ['email' => 'nadie@ejemplo.com'])->assertOk();

    expect($preguntas)->toContain($r1->json('pregunta'));
    expect($r1->json('pregunta'))->toBe($r2->json('pregunta'));
});

it('restablece la contraseña con la respuesta correcta, sin importar mayúsculas ni espacios', function () {
    $user = User::factory()->create([
        'pregunta_secreta' => preguntaValida(),
        'respuesta_secreta' => Hash::make('firulais'),
    ]);

    postJson('/reset-password/pregunta', [
        'email' => $user->email,
        'respuesta' => '  FiruLais ',
        'password' => 'NuevaClave123',
        'password_confirmation' => 'NuevaClave123',
    ])->assertOk()->assertJsonPath('message', __('passwords.reset'));

    expect(Hash::check('NuevaClave123', $user->fresh()->password))->toBeTrue();
});

it('no restablece con la respuesta incorrecta', function () {
    $user = User::factory()->create([
        'pregunta_secreta' => preguntaValida(),
        'respuesta_secreta' => Hash::make('firulais'),
    ]);

    postJson('/reset-password/pregunta', [
        'email' => $user->email,
        'respuesta' => 'otra cosa',
        'password' => 'NuevaClave123',
        'password_confirmation' => 'NuevaClave123',
    ])->assertUnprocessable()->assertJsonValidationErrors('respuesta');

    expect(Hash::check('NuevaClave123', $user->fresh()->password))->toBeFalse();
});

it('bloquea tras varios intentos fallidos', function () {
    $user = User::factory()->create([
        'pregunta_secreta' => preguntaValida(),
        'respuesta_secreta' => Hash::make('firulais'),
    ]);

    $intento = [
        'email' => $user->email,
        'respuesta' => 'incorrecta',
        'password' => 'NuevaClave123',
        'password_confirmation' => 'NuevaClave123',
    ];

    foreach (range(1, 5) as $i) {
        postJson('/reset-password/pregunta', $intento)->assertUnprocessable();
    }

    postJson('/reset-password/pregunta', $intento)->assertStatus(429);
});

it('rechaza una contraseña que no cumple la política', function () {
    $user = User::factory()->create([
        'pregunta_secreta' => preguntaValida(),
        'respuesta_secreta' => Hash::make('firulais'),
    ]);

    postJson('/reset-password/pregunta', [
        'email' => $user->email,
        'respuesta' => 'firulais',
        'password' => 'corta',
        'password_confirmation' => 'corta',
    ])->assertUnprocessable()->assertJsonValidationErrors('password');
});

it('revoca los tokens de la app al restablecer por pregunta', function () {
    $user = User::factory()->create([
        'pregunta_secreta' => preguntaValida(),
        'respuesta_secreta' => Hash::make('firulais'),
    ]);
    $user->createToken('movil');

    postJson('/reset-password/pregunta', [
        'email' => $user->email,
        'respuesta' => 'firulais',
        'password' => 'NuevaClave123',
        'password_confirmation' => 'NuevaClave123',
    ])->assertOk();

    expect($user->tokens()->count())->toBe(0);
});
