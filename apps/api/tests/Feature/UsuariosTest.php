<?php

use App\Enums\Rol;
use App\Models\Area;
use App\Models\User;
use Database\Seeders\RolesSeeder;
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
    Sanctum::actingAs(administrador());
    $areas = [area(), area()];

    postJson('/api/usuarios', [
        'name' => 'Encargado nuevo',
        'email' => 'nuevo@example.com',
        'password' => 'password123',
        'rol' => Rol::Encargado->value,
        'area_ids' => [$areas[0]->id, $areas[1]->id],
    ])
        ->assertCreated()
        ->assertJsonPath('name', 'Encargado nuevo')
        ->assertJsonPath('rol', 'encargado')
        ->assertJsonCount(2, 'areas');
});

it('edita un usuario y sincroniza sus áreas', function () {
    Sanctum::actingAs(administrador());
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

it('da de baja sin borrar el usuario y revoca sus tokens', function () {
    Sanctum::actingAs(administrador());
    $usuario = User::factory()->create();
    $usuario->assignRole(Rol::Ventas);

    deleteJson("/api/usuarios/{$usuario->id}")->assertNoContent();

    expect($usuario->fresh()->activo)->toBeFalse()
        ->and($usuario->fresh()->tokens)->toBeEmpty();
});

it('restringe la gestión de usuarios al administrador', function (Rol $rol) {
    $usuario = User::factory()->create();
    $usuario->assignRole($rol);
    Sanctum::actingAs($usuario);

    getJson('/api/usuarios')->assertForbidden();
    getJson('/api/areas-disponibles')->assertForbidden();
})->with([Rol::Ventas, Rol::Encargado]);
