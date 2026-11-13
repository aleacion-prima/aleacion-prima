<?php

use App\Enums\Rol;
use App\Events\UsuarioActualizado;
use App\Models\Area;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;
use function Pest\Laravel\seed;

beforeEach(function () {
    seed(RolesSeeder::class);
});

function administrador(): User
{
    $usuario = User::factory()->create();
    $usuario->assignRole(Rol::Administrador);

    return $usuario;
}

function area(): Area
{
    return Area::query()->create([
        'codigo' => fake()->unique()->lexify('???'),
        'nombre' => fake()->unique()->words(2, true),
        'tarifa_mano_obra_hora' => 10,
        'costo_maquina_hora' => 10,
        'orden_catalogo' => fake()->unique()->numberBetween(1, 100),
    ]);
}

it('permite al administrador crear un encargado con áreas', function () {
    Sanctum::actingAs(administrador(), ['*']);
    $areas = [area(), area()];

    postJson('/api/usuarios', [
        'name' => 'Encargado nuevo',
        'email' => 'nuevo@example.com',
        'password' => 'Password123',
        'rol' => Rol::Encargado->value,
        'area_ids' => [$areas[0]->id, $areas[1]->id],
    ])
        ->assertCreated()
        ->assertJsonPath('name', 'Encargado nuevo')
        ->assertJsonPath('rol', 'encargado')
        ->assertJsonCount(2, 'areas');
});

it('edita un usuario y sincroniza sus áreas', function () {
    Sanctum::actingAs(administrador(), ['*']);
    $usuario = User::factory()->create();
    $usuario->assignRole(Rol::Ventas);
    $area = area();

    putJson("/api/usuarios/{$usuario->id}", [
        'name' => 'Nombre editado',
        'email' => $usuario->email,
        'rol' => Rol::Encargado->value,
        'area_ids' => [$area->id],
    ])
        ->assertOk()
        ->assertJsonPath('name', 'Nombre editado')
        ->assertJsonPath('areas.0.id', $area->id);
});

it('ya no expone /api/areas-disponibles: las áreas activas salen de /api/areas?activo=1', function () {
    Sanctum::actingAs(administrador(), ['*']);
    $activa = area();
    $inactiva = area();
    $inactiva->update(['activo' => false]);

    getJson('/api/areas-disponibles')->assertNotFound();

    getJson('/api/areas?activo=1')
        ->assertOk()
        ->assertJsonFragment(['id' => $activa->id, 'nombre' => $activa->nombre])
        ->assertJsonMissing(['id' => $inactiva->id]);
});

it('da de baja sin borrar la cuenta, archiva el correo y revoca los tokens', function () {
    Sanctum::actingAs(administrador(), ['*']);
    $usuario = User::factory()->create(['email' => 'ventas@aleacionprima.test', 'activo' => true]);
    $usuario->assignRole(Rol::Ventas);
    $usuario->createToken('prueba');

    deleteJson("/api/usuarios/{$usuario->id}")->assertNoContent();

    $baja = $usuario->fresh();

    expect($baja->activo)->toBeFalse()
        ->and($baja->dado_de_baja_en)->not->toBeNull()
        ->and($baja->email_baja)->toBe('ventas@aleacionprima.test')
        ->and($baja->email)->toBe("baja.{$usuario->id}@baja.aleacionprima.invalid")
        ->and($baja->tokens)->toBeEmpty();
});

it('oculta a los usuarios dados de baja del listado', function () {
    Sanctum::actingAs(administrador(), ['*']);
    $enUso = User::factory()->create();
    $enUso->assignRole(Rol::Ventas);
    $baja = User::factory()->create();
    $baja->assignRole(Rol::Ventas);

    deleteJson("/api/usuarios/{$baja->id}")->assertNoContent();

    getJson('/api/usuarios')
        ->assertOk()
        ->assertJsonFragment(['id' => $enUso->id])
        ->assertJsonMissing(['id' => $baja->id]);
});

it('muestra a los dados de baja con incluir_bajas y su correo archivado', function () {
    Sanctum::actingAs(administrador(), ['*']);
    $baja = User::factory()->create(['email' => 'archivado@aleacionprima.test']);
    $baja->assignRole(Rol::Ventas);

    deleteJson("/api/usuarios/{$baja->id}")->assertNoContent();

    getJson('/api/usuarios?incluir_bajas=1')
        ->assertOk()
        ->assertJsonFragment([
            'id' => $baja->id,
            'email' => 'archivado@aleacionprima.test',
        ]);
});

it('mantiene visible al usuario inactivo que no está dado de baja', function () {
    Sanctum::actingAs(administrador(), ['*']);
    $inactivo = User::factory()->create(['activo' => false]);
    $inactivo->assignRole(Rol::Ventas);

    getJson('/api/usuarios')
        ->assertOk()
        ->assertJsonFragment(['id' => $inactivo->id, 'activo' => false]);
});

it('permite crear otra cuenta con el correo de un usuario dado de baja', function () {
    Sanctum::actingAs(administrador(), ['*']);
    $baja = User::factory()->create(['email' => 'reutilizable@aleacionprima.test']);
    $baja->assignRole(Rol::Ventas);

    deleteJson("/api/usuarios/{$baja->id}")->assertNoContent();

    postJson('/api/usuarios', [
        'name' => 'Cuenta nueva',
        'email' => 'reutilizable@aleacionprima.test',
        'password' => 'Password123',
        'rol' => Rol::Ventas->value,
    ])
        ->assertCreated()
        ->assertJsonPath('email', 'reutilizable@aleacionprima.test');
});

it('no permite dar de baja dos veces al mismo usuario', function () {
    Sanctum::actingAs(administrador(), ['*']);
    $usuario = User::factory()->create(['email' => 'doble@aleacionprima.test']);
    $usuario->assignRole(Rol::Ventas);

    deleteJson("/api/usuarios/{$usuario->id}")->assertNoContent();

    deleteJson("/api/usuarios/{$usuario->id}")
        ->assertStatus(422)
        ->assertJsonPath('message', 'Ese usuario ya está dado de baja.');

    // La segunda baja no debe pisar el correo real archivado con el placeholder.
    expect($usuario->fresh()->email_baja)->toBe('doble@aleacionprima.test');
});

it('no permite editar a un usuario dado de baja', function () {
    Sanctum::actingAs(administrador(), ['*']);
    $usuario = User::factory()->create();
    $usuario->assignRole(Rol::Ventas);

    deleteJson("/api/usuarios/{$usuario->id}")->assertNoContent();

    putJson("/api/usuarios/{$usuario->id}", [
        'name' => 'Nombre nuevo',
        'email' => $usuario->fresh()->email,
        'rol' => Rol::Ventas->value,
    ])
        ->assertStatus(422)
        ->assertJsonPath('message', 'Un usuario dado de baja ya no se edita.');

    expect($usuario->fresh()->name)->not->toBe('Nombre nuevo');
});

it('no permite que el administrador se dé de baja a sí mismo', function () {
    $administrador = administrador();
    Sanctum::actingAs($administrador, ['*']);

    deleteJson("/api/usuarios/{$administrador->id}")
        ->assertStatus(422)
        ->assertJsonPath('message', 'No puedes darte de baja a ti mismo.');

    expect($administrador->fresh()->activo)->toBeTrue();
});

it('no permite que el administrador se desactive a sí mismo', function () {
    $administrador = administrador();
    Sanctum::actingAs($administrador, ['*']);

    putJson("/api/usuarios/{$administrador->id}", [
        'name' => $administrador->name,
        'email' => $administrador->email,
        'rol' => Rol::Administrador->value,
        'activo' => false,
    ])
        ->assertStatus(422)
        ->assertJsonPath('message', 'No puedes desactivarte a ti mismo.');

    expect($administrador->fresh()->activo)->toBeTrue();
});

it('no permite que el administrador se quite su propio rol', function () {
    $administrador = administrador();
    Sanctum::actingAs($administrador, ['*']);

    putJson("/api/usuarios/{$administrador->id}", [
        'name' => $administrador->name,
        'email' => $administrador->email,
        'rol' => Rol::Ventas->value,
    ])
        ->assertStatus(422)
        ->assertJsonPath('message', 'No puedes quitarte el rol de administrador.');

    expect($administrador->fresh()->hasRole(Rol::Administrador))->toBeTrue();
});

it('permite que el administrador edite sus propios datos', function () {
    $administrador = administrador();
    Sanctum::actingAs($administrador, ['*']);

    putJson("/api/usuarios/{$administrador->id}", [
        'name' => 'Administrador renombrado',
        'email' => $administrador->email,
        'rol' => Rol::Administrador->value,
    ])
        ->assertOk()
        ->assertJsonPath('name', 'Administrador renombrado');
});

it('reactiva un usuario dado de baja', function () {
    Sanctum::actingAs(administrador(), ['*']);
    $usuario = User::factory()->create(['activo' => false]);
    $usuario->assignRole(Rol::Ventas);

    putJson("/api/usuarios/{$usuario->id}", [
        'name' => $usuario->name,
        'email' => $usuario->email,
        'rol' => Rol::Ventas->value,
        'activo' => true,
    ])
        ->assertOk()
        ->assertJsonPath('activo', true);

    expect($usuario->fresh()->activo)->toBeTrue();
});

it('restringe la gestión de usuarios al administrador', function (Rol $rol) {
    $usuario = User::factory()->create();
    $usuario->assignRole($rol);
    Sanctum::actingAs($usuario, ['*']);

    getJson('/api/usuarios')->assertForbidden();
    getJson('/api/areas')->assertForbidden();
})->with([Rol::Ventas, Rol::Encargado]);

it('avisa en vivo al usuario cuando el administrador lo edita', function () {
    Event::fake([UsuarioActualizado::class]);
    Sanctum::actingAs(administrador(), ['*']);
    $usuario = User::factory()->create();
    $usuario->assignRole(Rol::Encargado);

    putJson("/api/usuarios/{$usuario->id}", [
        'name' => 'Nombre editado',
        'email' => $usuario->email,
        'rol' => Rol::Encargado->value,
        'area_ids' => [area()->id],
    ])->assertOk();

    Event::assertDispatchedTimes(UsuarioActualizado::class, 1);
    Event::assertDispatched(UsuarioActualizado::class, fn (UsuarioActualizado $evento) => $evento->id === $usuario->id);
});

it('avisa en vivo al usuario cuando lo dan de baja', function () {
    Event::fake([UsuarioActualizado::class]);
    Sanctum::actingAs(administrador(), ['*']);
    $usuario = User::factory()->create();
    $usuario->assignRole(Rol::Encargado);

    deleteJson("/api/usuarios/{$usuario->id}")->assertNoContent();

    Event::assertDispatched(UsuarioActualizado::class, fn (UsuarioActualizado $evento) => $evento->id === $usuario->id);
});

it('no avisa nada si el cambio se rechaza', function () {
    Event::fake([UsuarioActualizado::class]);
    $administrador = administrador();
    Sanctum::actingAs($administrador, ['*']);

    deleteJson("/api/usuarios/{$administrador->id}")->assertStatus(422);
    putJson("/api/usuarios/{$administrador->id}", [
        'name' => $administrador->name,
        'email' => $administrador->email,
        'rol' => Rol::Ventas->value,
    ])->assertStatus(422);

    Event::assertNotDispatched(UsuarioActualizado::class);
});

it('el aviso va solo al canal privado del usuario y solo lleva su id', function () {
    $evento = new UsuarioActualizado(7);

    expect($evento)->toBeInstanceOf(ShouldDispatchAfterCommit::class)
        ->and($evento->broadcastOn()->name)->toBe('private-App.Models.User.7')
        ->and($evento->broadcastAs())->toBe('usuario.actualizado')
        ->and($evento->broadcastWith())->toBe(['id' => 7]);
});

it('el aviso sale hasta que se guardan los cambios', function () {
    Event::fake([UsuarioActualizado::class]);

    DB::transaction(function () {
        UsuarioActualizado::dispatch(7);
        Event::assertNotDispatched(UsuarioActualizado::class);
    });

    Event::assertDispatched(UsuarioActualizado::class);
});
