<?php

use App\Enums\EstadoLote;
use App\Enums\Rol;
use App\Models\Area;
use App\Models\Insumo;
use App\Models\Lote;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertAuthenticatedAs;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
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

    it('crea un lote de prueba con el primer producto activo', function () {
        seed(DatabaseSeeder::class);
        Sanctum::actingAs(usuarioDebug(Rol::Administrador), ['*']);

        postJson('/api/debug/lotes')
            ->assertCreated()
            ->assertJsonPath('estado', EstadoLote::Creado->value)
            ->assertJsonPath('cantidad_objetivo', 10);
    });

    it('solo el administrador crea lotes de prueba', function () {
        seed(DatabaseSeeder::class);
        Sanctum::actingAs(usuarioDebug(Rol::Ventas), ['*']);

        postJson('/api/debug/lotes')->assertForbidden();
        expect(Lote::query()->count())->toBe(0);
    });

    it('avanza un paso y luego hasta el final', function () {
        seed(DatabaseSeeder::class);
        Sanctum::actingAs(usuarioDebug(Rol::Administrador), ['*']);
        $codigo = postJson('/api/debug/lotes')->json('codigo');

        postJson("/api/debug/lotes/{$codigo}/avanzar")
            ->assertOk()
            ->assertJsonPath('estado', EstadoLote::EnProceso->value)
            ->assertJsonPath('paradas.0.piezas_recibidas', 10);

        postJson("/api/debug/lotes/{$codigo}/avanzar", ['hasta' => 'final'])
            ->assertOk()
            ->assertJsonPath('estado', EstadoLote::PorConfirmar->value);

        $lote = Lote::query()->where('codigo', $codigo)->firstOrFail();
        expect((float) $lote->costo_total)->toBeGreaterThan(0)
            ->and($lote->paradas()->where('salida_ocurrido_en', '>', now())->exists())->toBeFalse();

        postJson("/api/debug/lotes/{$codigo}/avanzar")->assertUnprocessable();
    });

    it('aplica la merma en la salida y el faltante en la siguiente entrada', function () {
        seed(DatabaseSeeder::class);
        Sanctum::actingAs(usuarioDebug(Rol::Administrador), ['*']);
        $codigo = postJson('/api/debug/lotes')->json('codigo');

        postJson("/api/debug/lotes/{$codigo}/avanzar");
        postJson("/api/debug/lotes/{$codigo}/avanzar", ['merma' => 2]);
        postJson("/api/debug/lotes/{$codigo}/avanzar", ['faltante' => 1])
            ->assertOk()
            ->assertJsonPath('paradas.0.piezas_entregadas', 8)
            ->assertJsonPath('paradas.0.merma_piezas', 2)
            ->assertJsonPath('paradas.1.piezas_recibidas', 7)
            ->assertJsonPath('paradas.1.discrepancia', -1);
    });

    it('avanza hasta el paso donde aplica la merma o el faltante', function () {
        seed(DatabaseSeeder::class);
        Sanctum::actingAs(usuarioDebug(Rol::Administrador), ['*']);
        $codigo = postJson('/api/debug/lotes')->json('codigo');

        postJson("/api/debug/lotes/{$codigo}/avanzar", ['merma' => 1])
            ->assertOk()
            ->assertJsonPath('paradas.0.piezas_entregadas', 9)
            ->assertJsonPath('paradas.0.merma_piezas', 1);

        postJson("/api/debug/lotes/{$codigo}/avanzar", ['merma' => 1])
            ->assertOk()
            ->assertJsonPath('paradas.1.piezas_recibidas', 9)
            ->assertJsonPath('paradas.1.merma_piezas', 1);
    });

    it('respeta los permisos de captura', function () {
        seed(DatabaseSeeder::class);
        Sanctum::actingAs(usuarioDebug(Rol::Administrador), ['*']);
        $codigo = postJson('/api/debug/lotes')->json('codigo');
        $lote = Lote::query()->where('codigo', $codigo)->firstOrFail();

        Sanctum::actingAs(usuarioDebug(Rol::Ventas), ['*']);
        postJson("/api/debug/lotes/{$codigo}/avanzar")->assertForbidden();

        $encargado = usuarioDebug(Rol::Encargado);
        $encargado->areas()->attach(Area::query()->whereKeyNot($lote->paradas()->orderBy('orden')->value('area_id'))->value('id'));
        Sanctum::actingAs($encargado, ['*']);
        postJson("/api/debug/lotes/{$codigo}/avanzar")->assertForbidden();

        $encargado->areas()->attach($lote->paradas()->orderBy('orden')->value('area_id'));
        postJson("/api/debug/lotes/{$codigo}/avanzar")->assertOk();
    });

    it('crea varios lotes repartidos sin terminar su recorrido', function () {
        seed(DatabaseSeeder::class);
        Sanctum::actingAs(usuarioDebug(Rol::Administrador), ['*']);
        postJson('/api/debug/inventario/rellenar')->assertNoContent();

        postJson('/api/debug/lotes/varios', ['lotes' => 6])
            ->assertOk()
            ->assertJsonCount(6);

        expect(Lote::query()->count())->toBe(6)
            ->and(Lote::query()->where('estado', EstadoLote::PorConfirmar)->exists())->toBeFalse();
    });

    it('rellena el inventario hasta la existencia de debug', function () {
        seed(DatabaseSeeder::class);
        Sanctum::actingAs(usuarioDebug(Rol::Administrador), ['*']);

        postJson('/api/debug/inventario/rellenar')->assertNoContent();

        Insumo::query()->where('activo', true)->each(function (Insumo $insumo) {
            expect((float) $insumo->existencia)->toBeGreaterThanOrEqual(10000.0);
        });
    });

    it('solo el administrador rellena el inventario y crea varios lotes', function () {
        seed(DatabaseSeeder::class);
        Sanctum::actingAs(usuarioDebug(Rol::Encargado), ['*']);

        postJson('/api/debug/inventario/rellenar')->assertForbidden();
        postJson('/api/debug/lotes/varios')->assertForbidden();
    });

    it('alterna todas las áreas del encargado', function () {
        seed(DatabaseSeeder::class);
        $encargado = usuarioDebug(Rol::Encargado);
        Sanctum::actingAs($encargado, ['*']);

        postJson('/api/debug/areas/alternar')->assertOk()->assertJsonCount(Area::query()->count(), 'areas');
        postJson('/api/debug/areas/alternar')->assertOk()->assertJsonCount(0, 'areas');
    });

    it('no alterna áreas si no es encargado', function () {
        Sanctum::actingAs(usuarioDebug(Rol::Administrador), ['*']);

        postJson('/api/debug/areas/alternar')->assertUnprocessable();
    });

    it('reinicia la BD con los seeders y cierra la sesión', function () {
        Artisan::shouldReceive('call')->once()->with('migrate:fresh', ['--seed' => true, '--force' => true])->andReturn(0);
        actingAs(usuarioDebug(Rol::Administrador), 'web');

        withHeaders(['Referer' => 'http://localhost:9000'])
            ->postJson('/api/debug/reiniciar')
            ->assertNoContent();

        assertGuest('web');
    });

    it('solo el administrador reinicia la BD', function () {
        Artisan::shouldReceive('call')->never();
        Sanctum::actingAs(usuarioDebug(Rol::Ventas), ['*']);

        postJson('/api/debug/reiniciar')->assertForbidden();
    });
});
