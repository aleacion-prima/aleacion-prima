<?php

use App\Bitacora\Bitacora;
use App\Bitacora\LectorBitacoraFake;
use App\Enums\AccionBitacora;
use App\Enums\EntidadBitacora;
use App\Enums\ResultadoBitacora;
use App\Enums\Rol;
use App\Models\Area;
use App\Models\Insumo;
use App\Models\Producto;
use App\Models\User;
use App\Observers\UserObserver;
use Database\Seeders\RolesSeeder;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Pennant\Feature;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\seed;
use function Pest\Laravel\travelTo;
use function Pest\Laravel\withToken;

beforeEach(function () {
    seed(RolesSeeder::class);
    travelTo('2026-11-02 09:00:00');
});

function auditoriaAdmin(string $nombre = 'Ana Pérez'): User
{
    $admin = User::factory()->create(['name' => $nombre]);
    $admin->assignRole(Rol::Administrador);

    return $admin;
}

/**
 * @param  list<array<string, mixed>>  $registros
 * @return list<array<string, mixed>>
 */
function eventosDe(array $registros, AccionBitacora $accion, ?EntidadBitacora $entidad = null): array
{
    return array_values(array_filter(
        $registros,
        fn (array $doc): bool => ($doc['accion'] ?? null) === $accion->value
            && ($entidad === null || ($doc['entidad'] ?? null) === $entidad->value),
    ));
}

/**
 * @param  list<array<string, mixed>>  $registros
 * @return array<string, mixed>
 */
function eventoDe(array $registros, AccionBitacora $accion, ?EntidadBitacora $entidad = null): array
{
    $eventos = eventosDe($registros, $accion, $entidad);

    expect($eventos)->not->toBeEmpty();

    return $eventos[0];
}

function insertarSesionAuditoria(User $usuario): string
{
    $id = Str::random(40);

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

it('registra crear, actualizar y eliminar un usuario con actor, fecha, resultado y cambios', function () {
    $admin = auditoriaAdmin();
    Sanctum::actingAs($admin, ['*']);
    $fake = Bitacora::fake();

    $usuario = User::factory()->create(['name' => 'Beto Luna']);
    $usuario->update(['name' => 'Beto Luna Actualizado']);
    $id = $usuario->id;
    $usuario->delete();

    $crear = eventoDe($fake->registros, AccionBitacora::Crear, EntidadBitacora::Usuario);
    $actualizar = eventoDe($fake->registros, AccionBitacora::Actualizar, EntidadBitacora::Usuario);
    $eliminar = eventoDe($fake->registros, AccionBitacora::Eliminar, EntidadBitacora::Usuario);

    expect($crear['entidad_id'])->toBe($id)
        ->and($crear['user_id'])->toBe($admin->id)
        ->and($crear['datos']['despues']['name'])->toBe('Beto Luna')
        ->and($actualizar['datos']['antes']['name'])->toBe('Beto Luna')
        ->and($actualizar['datos']['despues']['name'])->toBe('Beto Luna Actualizado')
        ->and($eliminar['datos']['antes']['name'])->toBe('Beto Luna Actualizado');

    foreach ([$crear, $actualizar, $eliminar] as $doc) {
        expect($doc['resultado'])->toBe(ResultadoBitacora::Exito->value)
            ->and($doc['descripcion'])->toContain('Ana Pérez')
            ->and($doc['registrado_en'])->not->toBeNull();
    }
});

it('registra crear, actualizar y eliminar cada catálogo', function (string $modelo, EntidadBitacora $entidad, string $nombre) {
    $admin = auditoriaAdmin();
    Sanctum::actingAs($admin, ['*']);
    $fake = Bitacora::fake();

    $registro = $modelo::factory()->create();
    $registro->update([$nombre => 'Valor cambiado']);
    $id = $registro->id;
    $registro->delete();

    $crear = eventoDe($fake->registros, AccionBitacora::Crear, $entidad);
    $actualizar = eventoDe($fake->registros, AccionBitacora::Actualizar, $entidad);
    $eliminar = eventoDe($fake->registros, AccionBitacora::Eliminar, $entidad);

    expect($crear['entidad_id'])->toBe($id)
        ->and($crear['user_id'])->toBe($admin->id)
        ->and($crear['datos']['despues'][$nombre])->not->toBe('Valor cambiado')
        ->and($actualizar['datos']['antes'][$nombre])->not->toBe('Valor cambiado')
        ->and($actualizar['datos']['despues'][$nombre])->toBe('Valor cambiado')
        ->and($eliminar['datos']['antes'][$nombre])->toBe('Valor cambiado');
})->with([
    [Producto::class, EntidadBitacora::Producto, 'nombre'],
    [Insumo::class, EntidadBitacora::Insumo, 'nombre'],
    [Area::class, EntidadBitacora::Area, 'nombre'],
]);

it('no registra una actualización sin cambios', function () {
    $admin = auditoriaAdmin();
    Sanctum::actingAs($admin, ['*']);
    $usuario = User::factory()->create();
    $fake = Bitacora::fake();

    $usuario->save();
    (new UserObserver)->updated($usuario);

    expect($fake->registros)->toBe([]);
});

it('no guarda contraseñas, respuestas secretas ni tokens en los cambios', function () {
    $admin = auditoriaAdmin();
    Sanctum::actingAs($admin, ['*']);
    $usuario = User::factory()->create();
    $fake = Bitacora::fake();

    $usuario->forceFill([
        'password' => bcrypt('OtroPassword123'),
        'respuesta_secreta' => 'RespuestaSecreta',
    ])->save();
    $usuario->setRememberToken('token-sensible');
    $usuario->save();

    $serializado = json_encode($fake->registros, JSON_THROW_ON_ERROR);

    expect($fake->acciones())->toContain(AccionBitacora::Actualizar->value)
        ->and($serializado)->not->toContain('password')
        ->and($serializado)->not->toContain('respuesta_secreta')
        ->and($serializado)->not->toContain('remember_token')
        ->and($serializado)->not->toContain('OtroPassword123')
        ->and($serializado)->not->toContain('RespuestaSecreta')
        ->and($serializado)->not->toContain('token-sensible');
});

it('registra el inicio y el cierre de sesión del panel', function () {
    config(['segundo-factor.obligatorio_administrador' => false]);
    $usuario = User::factory()->create(['name' => 'Vendedor Uno']);
    $usuario->assignRole(Rol::Ventas);

    $fake = Bitacora::fake();
    postJson('/login', ['email' => $usuario->email, 'password' => 'password'])->assertNoContent();

    $login = eventoDe($fake->registros, AccionBitacora::Login, EntidadBitacora::Usuario);
    expect($login['entidad_id'])->toBe($usuario->id)
        ->and($login['user_id'])->toBe($usuario->id)
        ->and($login['resultado'])->toBe(ResultadoBitacora::Exito->value)
        ->and($login['descripcion'])->toContain('Vendedor Uno');

    $fake = Bitacora::fake();
    postJson('/logout')->assertNoContent();

    $logout = eventoDe($fake->registros, AccionBitacora::Logout, EntidadBitacora::Usuario);
    expect($logout['entidad_id'])->toBe($usuario->id)
        ->and($logout['resultado'])->toBe(ResultadoBitacora::Exito->value);
});

it('no revienta ni registra nada cuando el logout llega sin usuario (pre-logout del 2FA)', function () {
    // SesionController@iniciar hace Auth::guard('web')->logout() al entrar
    // alguien con 2FA, sin sesión activa: el evento Logout llega con user=null.
    $fake = Bitacora::fake();

    event(new Logout('web', null));

    expect($fake->registros)->toBe([]);
});

it('registra un inicio de sesión fallido como error sin guardar la contraseña', function () {
    $usuario = User::factory()->create(['email' => 'fallo@example.com']);
    $fake = Bitacora::fake();

    postJson('/login', ['email' => $usuario->email, 'password' => 'incorrecta'])
        ->assertStatus(422);

    $doc = eventoDe($fake->registros, AccionBitacora::Login, EntidadBitacora::Usuario);

    expect($doc['resultado'])->toBe(ResultadoBitacora::Error->value)
        ->and($doc['datos']['email'])->toBe($usuario->email)
        ->and($doc['descripcion'])->toContain('Intento de inicio de sesión fallido')
        ->and(json_encode($doc, JSON_THROW_ON_ERROR))->not->toContain('incorrecta');
});

it('registra el cierre remoto de una sesión y de todas las sesiones', function () {
    $admin = auditoriaAdmin();
    $otro = User::factory()->create(['name' => 'Carlos Dueño']);
    $id = insertarSesionAuditoria($otro);
    $otro->createToken('movil', ['lotes:read']);
    Sanctum::actingAs($admin, ['*']);

    $fake = Bitacora::fake();
    deleteJson("/api/usuarios/{$otro->id}/sesiones/panel:{$id}")->assertNoContent();

    $uno = eventoDe($fake->registros, AccionBitacora::CerrarSesiones, EntidadBitacora::Usuario);
    expect($uno['entidad_id'])->toBe($otro->id)
        ->and($uno['user_id'])->toBe($admin->id)
        ->and($uno['datos']['cantidad'])->toBe(1)
        ->and($uno['datos']['sesiones'][0]['id'])->toBe($id);

    insertarSesionAuditoria($otro);
    $fake = Bitacora::fake();
    deleteJson("/api/usuarios/{$otro->id}/sesiones")->assertNoContent();

    $todas = eventoDe($fake->registros, AccionBitacora::CerrarSesiones, EntidadBitacora::Usuario);
    expect($todas['entidad_id'])->toBe($otro->id)
        ->and($todas['user_id'])->toBe($admin->id)
        ->and($todas['datos']['cantidad'])->toBeGreaterThan(0)
        ->and($todas['datos']['tokens'])->not->toBeEmpty();
});

it('registra el cambio de estado de un módulo con su nombre y quién lo cambió', function () {
    $admin = auditoriaAdmin();
    Sanctum::actingAs($admin, ['*']);
    $fake = Bitacora::fake();

    Feature::activate('ventas');
    Feature::deactivate('ventas');

    $eventos = eventosDe($fake->registros, AccionBitacora::CambiarModulo, EntidadBitacora::Modulo);
    expect($eventos)->toHaveCount(2)
        ->and($eventos[0]['entidad_id'])->toBeNull()
        ->and($eventos[0]['user_id'])->toBe($admin->id)
        ->and($eventos[0]['datos']['modulo'])->toBe('ventas')
        ->and($eventos[0]['datos']['activo'])->toBeTrue()
        ->and($eventos[0]['descripcion'])->toContain('activó el módulo ventas')
        ->and($eventos[1]['datos']['activo'])->toBeFalse()
        ->and($eventos[1]['descripcion'])->toContain('desactivó el módulo ventas');
});

it('solo el administrador del panel consulta la bitácora general', function () {
    $admin = auditoriaAdmin();
    $ventas = User::factory()->create();
    $ventas->assignRole(Rol::Ventas);
    $encargado = User::factory()->create();
    $encargado->assignRole(Rol::Encargado);
    $movil = User::factory()->create();
    $movil->assignRole(Rol::Administrador);
    $token = $movil->createToken('movil', ['lotes:read'])->plainTextToken;

    Sanctum::actingAs($ventas, ['*']);
    getJson('/api/bitacora')->assertForbidden();

    Sanctum::actingAs($encargado, ['*']);
    getJson('/api/bitacora')->assertForbidden();

    withToken($token)->getJson('/api/bitacora')->assertForbidden();

    Sanctum::actingAs($admin, ['*']);
    getJson('/api/bitacora')->assertOk();
});

it('acota la bitácora general con los filtros y la paginación', function () {
    $admin = auditoriaAdmin();
    $otro = User::factory()->create(['name' => 'Registrador Uno']);
    $documentos = [
        [
            'user_id' => $otro->id, 'accion' => 'crear', 'resultado' => 'exito', 'entidad' => 'usuario',
            'descripcion' => 'Alta temprana', 'datos' => [],
            'ocurrido_en' => '2026-11-02 08:00', 'registrado_en' => '2026-11-02 08:00', 'captura_tardia' => false,
        ],
        [
            'user_id' => $otro->id, 'accion' => 'login', 'resultado' => 'exito', 'entidad' => 'usuario',
            'descripcion' => 'Acceso correcto', 'datos' => [],
            'ocurrido_en' => '2026-11-02 10:00', 'registrado_en' => '2026-11-02 10:00', 'captura_tardia' => false,
        ],
        [
            'user_id' => $admin->id, 'accion' => 'actualizar', 'resultado' => 'error', 'entidad' => 'producto',
            'descripcion' => 'Cambio fallido', 'datos' => [],
            'ocurrido_en' => '2026-11-03 10:00', 'registrado_en' => '2026-11-03 10:00', 'captura_tardia' => false,
        ],
        [
            'user_id' => $otro->id, 'accion' => 'login', 'resultado' => 'error', 'entidad' => 'usuario',
            'descripcion' => 'Acceso fallido', 'datos' => [],
            'ocurrido_en' => '2026-11-02 12:00', 'registrado_en' => '2026-11-02 12:00', 'captura_tardia' => false,
        ],
    ];
    LectorBitacoraFake::con($documentos);

    Sanctum::actingAs($admin, ['*']);

    getJson('/api/bitacora')
        ->assertOk()
        ->assertJsonCount(4, 'data')
        ->assertJsonPath('meta.total', 4)
        ->assertJsonPath('data.0.descripcion', 'Cambio fallido')
        ->assertJsonPath('data.0.accion', 'actualizar')
        ->assertJsonPath('data.0.usuario.id', $admin->id)
        ->assertJsonPath('data.0.usuario.nombre', $admin->name)
        ->assertJsonPath('data.0.fecha', '2026-11-03T10:00:00.000000Z');

    getJson('/api/bitacora?accion=login')->assertOk()->assertJsonCount(2, 'data');
    getJson('/api/bitacora?resultado=error')->assertOk()->assertJsonCount(2, 'data');
    getJson('/api/bitacora?entidad=producto')->assertOk()->assertJsonCount(1, 'data');
    getJson('/api/bitacora?usuario='.$otro->id)->assertOk()->assertJsonCount(3, 'data');
    getJson('/api/bitacora?desde=2026-11-03&hasta=2026-11-03')->assertOk()->assertJsonCount(1, 'data');
    getJson('/api/bitacora?ocultar_logins=1')->assertOk()->assertJsonCount(2, 'data');
    getJson('/api/bitacora?accion=login&ocultar_logins=1')->assertOk()->assertJsonCount(0, 'data');
});
