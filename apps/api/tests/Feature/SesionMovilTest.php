<?php

use App\Enums\Rol;
use App\Models\Lote;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Laravel\Sanctum\PersonalAccessToken;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;
use function Pest\Laravel\seed;
use function Pest\Laravel\withToken;

beforeEach(function () {
    seed(RolesSeeder::class);
});

function usuarioMovil(Rol $rol, bool $activo = true): User
{
    $usuario = User::factory()->create(['activo' => $activo]);
    $usuario->assignRole($rol);

    return $usuario;
}

function tokenMovil(User $usuario): string
{
    return postJson('/api/movil/login', ['email' => $usuario->email, 'password' => 'password'])
        ->assertCreated()
        ->json('token');
}

it('entrega un token de solo lectura a encargados y administradores activos', function (Rol $rol) {
    $usuario = usuarioMovil($rol);

    postJson('/api/movil/login', ['email' => $usuario->email, 'password' => 'password'])
        ->assertCreated()
        ->assertJsonStructure(['token', 'usuario' => ['id', 'name', 'email', 'rol', 'areas']])
        ->assertJsonPath('usuario.rol', $rol->value);

    $token = PersonalAccessToken::query()->sole();
    expect($token->name)->toBe('movil')
        ->and($token->abilities)->toBe(['lotes:read'])
        ->and($token->tokenable_id)->toBe($usuario->id);
})->with([Rol::Encargado, Rol::Administrador]);

it('no entrega token a roles sin acceso a la app', function (Rol $rol) {
    $usuario = usuarioMovil($rol);

    postJson('/api/movil/login', ['email' => $usuario->email, 'password' => 'password'])
        ->assertForbidden();

    assertDatabaseCount('personal_access_tokens', 0);
})->with([Rol::Ventas, Rol::Cliente]);

it('rechaza credenciales inválidas y usuarios inactivos con el mismo error', function (bool $activo, string $password) {
    $usuario = usuarioMovil(Rol::Encargado, $activo);

    postJson('/api/movil/login', ['email' => $usuario->email, 'password' => $password])
        ->assertUnprocessable()
        ->assertJsonPath('errors.credenciales.0', 'Correo o contraseña incorrectos.');

    assertDatabaseCount('personal_access_tokens', 0);
})->with([
    'contraseña incorrecta' => [true, 'otra'],
    'usuario inactivo' => [false, 'password'],
]);

it('limita los intentos de inicio de sesión', function () {
    $usuario = usuarioMovil(Rol::Encargado);

    foreach (range(1, 5) as $intento) {
        postJson('/api/movil/login', ['email' => $usuario->email, 'password' => 'otra'])
            ->assertUnprocessable();
    }

    postJson('/api/movil/login', ['email' => $usuario->email, 'password' => 'password'])
        ->assertTooManyRequests();
});

it('con el token móvil se leen los lotes', function () {
    $lote = Lote::factory()->create();
    $token = tokenMovil(usuarioMovil(Rol::Administrador));

    withToken($token)->getJson('/api/lotes')->assertOk();
    withToken($token)->getJson("/api/lotes/{$lote->codigo}")->assertOk()
        ->assertJsonPath('codigo', $lote->codigo);
    withToken($token)->getJson('/api/user')->assertOk();
});

it('con el token móvil se rechaza todo lo que no sea lectura de lotes', function (string $metodo, string $ruta) {
    Lote::factory()->create(['codigo' => 'LOT-20261111-01']);
    $token = tokenMovil(usuarioMovil(Rol::Administrador));

    withToken($token)->json($metodo, $ruta)->assertForbidden();
})->with([
    'crear lote' => ['POST', '/api/lotes'],
    'cancelar lote' => ['POST', '/api/lotes/LOT-20261111-01/cancelar'],
    'confirmar lote' => ['POST', '/api/lotes/LOT-20261111-01/confirmar'],
    'listar áreas' => ['GET', '/api/areas'],
    'crear usuario' => ['POST', '/api/usuarios'],
    'pregunta secreta' => ['PUT', '/api/perfil/pregunta-secreta'],
]);

it('al cerrar sesión borra el token y deja de servir', function () {
    $token = tokenMovil(usuarioMovil(Rol::Encargado));

    withToken($token)->postJson('/api/logout')->assertNoContent();

    assertDatabaseCount('personal_access_tokens', 0);

    app('auth')->forgetGuards();
    withToken($token)->getJson('/api/lotes')->assertUnauthorized();
});

it('cerrar sesión solo borra el token del dispositivo que la cierra', function () {
    $usuario = usuarioMovil(Rol::Encargado);
    $token = tokenMovil($usuario);
    tokenMovil($usuario);

    withToken($token)->postJson('/api/logout')->assertNoContent();

    assertDatabaseCount('personal_access_tokens', 1);
});

it('la sesión del panel sigue pudiendo leer y escribir', function () {
    $admin = usuarioMovil(Rol::Administrador);

    postJson('/login', ['email' => $admin->email, 'password' => 'password'])->assertNoContent();

    getJson('/api/lotes')->assertOk();
    getJson('/api/areas')->assertOk();
    putJson('/api/perfil/pregunta-secreta', [])->assertUnprocessable();
});
