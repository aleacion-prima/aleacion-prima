<?php

use App\Enums\Rol;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;
use function Pest\Laravel\seed;

beforeEach(function () {
    seed(RolesSeeder::class);

    Route::middleware(['auth:sanctum', 'activo', 'role:administrador|ventas'])
        ->get('/api/prueba/ventas', fn () => response()->noContent());
    Route::middleware(['auth:sanctum', 'activo', 'role:administrador|encargado'])
        ->get('/api/prueba/captura', fn () => response()->noContent());
    Route::middleware(['auth:sanctum', 'activo', 'role:administrador'])
        ->get('/api/prueba/administracion', fn () => response()->noContent());
});

$conRol = function (Rol $rol): User {
    $user = User::factory()->create();
    $user->assignRole($rol);

    return $user;
};

it('responde 401 sin sesión', function () {
    getJson('/api/prueba/ventas')->assertUnauthorized();
});

it('responde 403 a un rol que no le toca la ruta', function () use ($conRol) {
    Sanctum::actingAs($conRol(Rol::Encargado));

    getJson('/api/prueba/ventas')->assertForbidden();
    getJson('/api/prueba/administracion')->assertForbidden();
});

it('deja pasar al rol de la ruta', function () use ($conRol) {
    Sanctum::actingAs($conRol(Rol::Ventas));
    getJson('/api/prueba/ventas')->assertNoContent();

    Sanctum::actingAs($conRol(Rol::Encargado));
    getJson('/api/prueba/captura')->assertNoContent();
});

it('el administrador puede todo lo de ventas y encargado', function () use ($conRol) {
    Sanctum::actingAs($conRol(Rol::Administrador));

    getJson('/api/prueba/ventas')->assertNoContent();
    getJson('/api/prueba/captura')->assertNoContent();
    getJson('/api/prueba/administracion')->assertNoContent();
});
