<?php

use App\Enums\Rol;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertAuthenticatedAs;
use function Pest\Laravel\getJson;
use function Pest\Laravel\seed;
use function Pest\Laravel\withHeaders;

beforeEach(function () {
    seed(RolesSeeder::class);
});

function usuarioDebug(Rol $rol, bool $activo = true): User
{
    $usuario = User::factory()->create(['activo' => $activo]);
    $usuario->assignRole($rol);

    return $usuario;
}

function cargarRutasDebug(): void
{
    Route::middleware(['api', 'auth:sanctum', 'activo'])
        ->prefix('api/debug')
        ->group(base_path('routes/debug.php'));
}

it('no registra las rutas de debug fuera de local', function () {
    Sanctum::actingAs(usuarioDebug(Rol::Administrador), ['*']);

    expect(app()->isLocal())->toBeFalse();
    getJson('/api/debug/usuarios')->assertNotFound();
});

describe('con las rutas cargadas', function () {
    beforeEach(fn () => cargarRutasDebug());

    it('lista los usuarios activos con su rol para cualquier rol', function (Rol $rol) {
        $sesion = usuarioDebug($rol);
        usuarioDebug(Rol::Administrador, activo: false);
        Sanctum::actingAs($sesion, ['*']);

        getJson('/api/debug/usuarios')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $sesion->id)
            ->assertJsonPath('0.rol', $rol->value);
    })->with([Rol::Administrador, Rol::Encargado, Rol::Ventas]);

    it('pide sesión', function () {
        getJson('/api/debug/usuarios')->assertUnauthorized();
    });

    it('entrar como cambia la sesión al usuario elegido', function () {
        $encargado = usuarioDebug(Rol::Encargado);
        actingAs(usuarioDebug(Rol::Administrador), 'web');

        withHeaders(['Referer' => 'http://localhost:9000'])
            ->postJson("/api/debug/entrar-como/{$encargado->id}")
            ->assertOk()
            ->assertJsonPath('id', $encargado->id)
            ->assertJsonPath('rol', Rol::Encargado->value);

        assertAuthenticatedAs($encargado, 'web');
    });

    it('no entra como un usuario desactivado', function () {
        $inactivo = usuarioDebug(Rol::Encargado, activo: false);
        $admin = usuarioDebug(Rol::Administrador);
        actingAs($admin, 'web');

        withHeaders(['Referer' => 'http://localhost:9000'])
            ->postJson("/api/debug/entrar-como/{$inactivo->id}")
            ->assertUnprocessable();

        assertAuthenticatedAs($admin, 'web');
    });
});
