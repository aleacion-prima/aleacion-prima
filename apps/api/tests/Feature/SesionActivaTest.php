<?php

use App\Acceso\SesionesActivas;
use App\Enums\Rol;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\seed;
use function Pest\Laravel\withToken;

/**
 * En pruebas SESSION_DRIVER=array, así que /login no escribe en la tabla
 * `sessions`. Insertamos filas falsas a mano para ejercitar las consultas.
 */
function insertarSesion(User $usuario, ?string $id = null): string
{
    $id ??= Str::random(40);

    DB::table('sessions')->insert([
        'id' => $id,
        'user_id' => $usuario->id,
        'ip_address' => '10.0.0.1',
        'user_agent' => 'Mozilla/5.0 (Windows NT 10.0) Chrome/120.0',
        'payload' => 'a:0:{}',
        'last_activity' => time(),
    ]);

    return $id;
}

it('lista las sesiones del panel y los tokens de la app', function () {
    $usuario = User::factory()->create();
    insertarSesion($usuario);
    insertarSesion($usuario);
    $usuario->createToken('movil', ['lotes:read']);

    Sanctum::actingAs($usuario, ['*']);

    $respuesta = getJson('/api/sesiones')->assertOk();

    expect($respuesta->json())->toHaveCount(3);
    $tipos = collect($respuesta->json())->countBy('tipo');
    expect($tipos['panel'])->toBe(2);
    expect($tipos['app'])->toBe(1);
});

it('cierra una sesión del panel', function () {
    $usuario = User::factory()->create();
    $id = insertarSesion($usuario);

    Sanctum::actingAs($usuario, ['*']);

    deleteJson("/api/sesiones/panel:{$id}")->assertNoContent();

    assertDatabaseMissing('sessions', ['id' => $id]);
});

it('cerrar un token lo desconecta en la siguiente petición', function () {
    seed(RolesSeeder::class);
    $usuario = User::factory()->create();
    $usuario->assignRole(Rol::Encargado);

    $token = $usuario->createToken('movil', ['lotes:read']);
    $plain = $token->plainTextToken;

    // Con el token válido la app entra a la lectura de lotes.
    withToken($plain)->getJson('/api/lotes')->assertOk();

    // Desde la sesión del panel se revoca ese token.
    Sanctum::actingAs($usuario, ['*']);
    deleteJson("/api/sesiones/app:{$token->accessToken->id}")->assertNoContent();

    // Soltamos la sesión del panel para que la siguiente petición vuelva a
    // resolverse por el token (ya borrado): la app queda desconectada.
    app('auth')->forgetGuards();
    withToken($plain)->getJson('/api/lotes')->assertUnauthorized();
});

it('cierra todas las sesiones del panel y los tokens', function () {
    $usuario = User::factory()->create();
    insertarSesion($usuario);
    insertarSesion($usuario);
    $usuario->createToken('movil', ['lotes:read']);

    // Sin sesión de navegador (p. ej. un token con ability panel): no hay "actual"
    // que excluir, así que se cierran todas.
    Sanctum::actingAs($usuario, ['*']);

    deleteJson('/api/sesiones')->assertNoContent();

    expect(DB::table('sessions')->where('user_id', $usuario->id)->count())->toBe(0);
    expect($usuario->tokens()->count())->toBe(0);
});

it('al cerrar las demás conserva la sesión actual y revoca el resto', function () {
    $usuario = User::factory()->create();
    $actual = insertarSesion($usuario);
    insertarSesion($usuario); // otra sesión del panel
    $usuario->createToken('movil', ['lotes:read']);

    // El id de la sesión actual lo aporta la cookie en la petición real; aquí
    // ejercitamos el servicio directamente porque en pruebas (SESSION_DRIVER=array)
    // la petición HTTP no conserva la cookie de sesión entre llamadas.
    app(SesionesActivas::class)->revocarTodas($usuario, $actual);

    // Sobrevive solo la actual; las demás y todos los tokens, no.
    expect(DB::table('sessions')->where('user_id', $usuario->id)->count())->toBe(1);
    assertDatabaseHas('sessions', ['id' => $actual]);
    expect($usuario->tokens()->count())->toBe(0);
});

it('cerrar una sesión del panel borra su fila y rota el "recordarme"', function () {
    config(['session.driver' => 'database']);
    seed(RolesSeeder::class);
    $usuario = User::factory()->create();
    $usuario->assignRole(Rol::Ventas);

    // Login "recordándome": Auth::login guarda un remember_token y el driver
    // database deja la fila de la sesión.
    postJson('/login', ['email' => $usuario->email, 'password' => 'password', 'recordar' => true])
        ->assertNoContent();

    $id = DB::table('sessions')->where('user_id', $usuario->id)->value('id');
    $rememberAntes = $usuario->fresh()?->getRememberToken();
    expect($rememberAntes)->not->toBeNull();

    deleteJson("/api/sesiones/panel:{$id}")->assertNoContent();

    // Sin fila de sesión, la siguiente petición no se autentica; y al rotar el
    // remember_token, la cookie remember_web_* del dispositivo tampoco lo
    // vuelve a autenticar (que era el hueco: borrar la fila no bastaba).
    assertDatabaseMissing('sessions', ['id' => $id]);
    expect($usuario->fresh()?->getRememberToken())
        ->not->toBeNull()
        ->not->toBe($rememberAntes);
});

it('no deja cerrar sesiones ajenas', function () {
    $a = User::factory()->create();
    $b = User::factory()->create();
    $idDeB = insertarSesion($b);

    Sanctum::actingAs($a, ['*']);

    deleteJson("/api/sesiones/panel:{$idDeB}")->assertNotFound();

    assertDatabaseHas('sessions', ['id' => $idDeB]);
});

it('solo el administrador ve y cierra las sesiones de otro usuario', function () {
    seed(RolesSeeder::class);
    $otro = User::factory()->create();

    $ventas = User::factory()->create();
    $ventas->assignRole(Rol::Ventas);
    Sanctum::actingAs($ventas, ['*']);
    getJson("/api/usuarios/{$otro->id}/sesiones")->assertForbidden();

    $admin = User::factory()->create();
    $admin->assignRole(Rol::Administrador);
    Sanctum::actingAs($admin, ['*']);
    getJson("/api/usuarios/{$otro->id}/sesiones")->assertOk();
});
