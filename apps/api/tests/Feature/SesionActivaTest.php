<?php

use App\Enums\Rol;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\seed;

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

    $this->assertDatabaseMissing('sessions', ['id' => $id]);
});

it('cerrar un token lo desconecta en la siguiente petición', function () {
    seed(RolesSeeder::class);
    $usuario = User::factory()->create();
    $usuario->assignRole(Rol::Encargado);

    $token = $usuario->createToken('movil', ['lotes:read']);
    $plain = $token->plainTextToken;

    // Con el token válido la app entra a la lectura de lotes.
    $this->withToken($plain)->getJson('/api/lotes')->assertOk();

    // Desde la sesión del panel se revoca ese token.
    Sanctum::actingAs($usuario, ['*']);
    deleteJson("/api/sesiones/app:{$token->accessToken->id}")->assertNoContent();

    // Soltamos la sesión del panel para que la siguiente petición vuelva a
    // resolverse por el token (ya borrado): la app queda desconectada.
    $this->app['auth']->forgetGuards();
    $this->withToken($plain)->getJson('/api/lotes')->assertUnauthorized();
});

it('cierra todas las sesiones menos la actual', function () {
    $usuario = User::factory()->create();
    insertarSesion($usuario);
    insertarSesion($usuario);
    $usuario->createToken('movil', ['lotes:read']);

    Sanctum::actingAs($usuario, ['*']);

    deleteJson('/api/sesiones')->assertNoContent();

    expect(DB::table('sessions')->where('user_id', $usuario->id)->count())->toBe(0);
    expect($usuario->tokens()->count())->toBe(0);
});

it('no deja cerrar sesiones ajenas', function () {
    $a = User::factory()->create();
    $b = User::factory()->create();
    $idDeB = insertarSesion($b);

    Sanctum::actingAs($a, ['*']);

    deleteJson("/api/sesiones/panel:{$idDeB}")->assertNotFound();

    $this->assertDatabaseHas('sessions', ['id' => $idDeB]);
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
