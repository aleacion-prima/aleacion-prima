<?php

use App\Enums\EstadoLote;
use App\Enums\Rol;
use App\Models\Lote;
use App\Models\LoteArea;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;
use function Pest\Laravel\seed;
use function Pest\Laravel\withToken;

beforeEach(function () {
    seed(RolesSeeder::class);
});

function usuarioTablero(Rol $rol): User
{
    $usuario = User::factory()->create();
    $usuario->assignRole($rol);

    return $usuario;
}

it('lista solo los lotes creados, en proceso o por confirmar, con su recorrido', function () {
    $activos = collect([EstadoLote::Creado, EstadoLote::EnProceso, EstadoLote::PorConfirmar])
        ->map(fn (EstadoLote $estado) => Lote::factory()->create(['estado' => $estado]));
    Lote::factory()->create(['estado' => EstadoLote::Terminado]);
    Lote::factory()->create(['estado' => EstadoLote::Cancelado]);
    LoteArea::factory()->for($activos->first())->create();
    Sanctum::actingAs(usuarioTablero(Rol::Administrador), ['*']);

    $respuesta = getJson('/api/tablero')->assertOk();

    expect(collect($respuesta->json())->pluck('codigo')->sort()->values()->all())
        ->toBe($activos->pluck('codigo')->sort()->values()->all());
    $respuesta->assertJsonStructure([['codigo', 'estado', 'producto', 'area_actual', 'paradas', 'costo_total']]);
});

it('solo lo ve el administrador', function (Rol $rol) {
    Sanctum::actingAs(usuarioTablero($rol), ['*']);

    getJson('/api/tablero')->assertForbidden();
})->with([Rol::Ventas, Rol::Encargado, Rol::Cliente]);

it('la app móvil no puede leer el tablero', function () {
    $admin = usuarioTablero(Rol::Administrador);
    $token = $admin->createToken('movil', ['lotes:read'])->plainTextToken;

    withToken($token)->getJson('/api/tablero')->assertForbidden();
});

it('pide iniciar sesión', function () {
    getJson('/api/tablero')->assertUnauthorized();
});
