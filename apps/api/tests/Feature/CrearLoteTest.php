<?php

use App\Enums\EstadoLote;
use App\Enums\Rol;
use App\Enums\TipoMovimientoInsumo;
use App\Models\Area;
use App\Models\Insumo;
use App\Models\Lote;
use App\Models\MovimientoInsumo;
use App\Models\Producto;
use App\Models\User;
use Database\Seeders\AreaSeeder;
use Database\Seeders\RolesSeeder;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\seed;
use function Pest\Laravel\travelTo;

beforeEach(function () {
    seed([RolesSeeder::class, AreaSeeder::class]);
    travelTo('2026-11-02 09:00:00');
});

function entrarALotesComo(Rol $rol): User
{
    $usuario = User::factory()->create();
    $usuario->assignRole($rol);
    Sanctum::actingAs($usuario);

    return $usuario;
}

function areaDeLote(string $codigo): Area
{
    return Area::query()->where('codigo', $codigo)->firstOrFail();
}

/**
 * @param  list<string>  $codigos
 * @return list<int>
 */
function idsDeAreas(array $codigos): array
{
    return array_map(fn (string $codigo) => areaDeLote($codigo)->id, $codigos);
}

function productoDeLote(): Producto
{
    $producto = Producto::factory()->create(['codigo' => 'DIS-FRE-G']);

    foreach (['MOL', 'FUS', 'COL', 'DES', 'GRA', 'CAL'] as $indice => $codigo) {
        $producto->areas()->attach(areaDeLote($codigo)->id, ['orden' => $indice + 1]);
    }

    $insumos = [
        ['codigo' => 'CHT-FUN', 'categoria' => 'metal', 'costo_unitario' => 7, 'existencia' => 1000, 'por_pieza' => 10],
        ['codigo' => 'FES-75', 'categoria' => 'metal', 'costo_unitario' => 40, 'existencia' => 50, 'por_pieza' => 0.5],
        ['codigo' => 'ARE-VER', 'categoria' => 'moldeo', 'costo_unitario' => 1.2, 'existencia' => 5000, 'por_pieza' => 20],
    ];

    foreach ($insumos as $datos) {
        $insumo = Insumo::factory()->create([
            'codigo' => $datos['codigo'],
            'categoria' => $datos['categoria'],
            'unidad' => 'kg',
            'costo_unitario' => $datos['costo_unitario'],
            'existencia' => $datos['existencia'],
        ]);
        $producto->insumos()->attach($insumo->id, ['cantidad_por_pieza' => $datos['por_pieza']]);
    }

    return $producto;
}

function insumoDeLote(string $codigo): Insumo
{
    return Insumo::query()->where('codigo', $codigo)->firstOrFail();
}

it('crea el lote con el código del día y en estado creado', function () {
    $admin = entrarALotesComo(Rol::Administrador);
    $producto = productoDeLote();

    postJson('/api/lotes', ['producto_id' => $producto->id, 'cantidad_objetivo' => 10])
        ->assertCreated()
        ->assertJsonPath('codigo', 'LOT-20261102-01')
        ->assertJsonPath('estado', 'creado');

    $lote = Lote::query()->where('codigo', 'LOT-20261102-01')->firstOrFail();
    expect($lote->estado)->toBe(EstadoLote::Creado)
        ->and($lote->cantidad_objetivo)->toBe(10)
        ->and($lote->creado_por)->toBe($admin->id);
});

it('lleva el consecutivo del día y lo reinicia al día siguiente', function () {
    entrarALotesComo(Rol::Administrador);
    $producto = productoDeLote();
    $datos = ['producto_id' => $producto->id, 'cantidad_objetivo' => 1];

    postJson('/api/lotes', $datos)->assertJsonPath('codigo', 'LOT-20261102-01');
    postJson('/api/lotes', $datos)->assertJsonPath('codigo', 'LOT-20261102-02');

    travelTo('2026-11-03 08:00:00');

    postJson('/api/lotes', $datos)->assertJsonPath('codigo', 'LOT-20261103-01');
});

it('usa la fecha de la planta y no la del servidor', function () {
    entrarALotesComo(Rol::Administrador);
    $producto = productoDeLote();
    travelTo('2026-11-03 01:30:00');

    postJson('/api/lotes', ['producto_id' => $producto->id, 'cantidad_objetivo' => 1])
        ->assertJsonPath('codigo', 'LOT-20261102-01');
});

it('clona el recorrido por defecto del producto con las tarifas congeladas', function () {
    entrarALotesComo(Rol::Administrador);
    $producto = productoDeLote();

    postJson('/api/lotes', ['producto_id' => $producto->id, 'cantidad_objetivo' => 10])
        ->assertCreated()
        ->assertJsonCount(6, 'paradas')
        ->assertJsonPath('paradas.0.area.codigo', 'MOL')
        ->assertJsonPath('paradas.5.area.codigo', 'CAL');

    areaDeLote('MOL')->update(['tarifa_mano_obra_hora' => 999]);

    $parada = Lote::query()->firstOrFail()->paradas()->firstOrFail();
    expect($parada->orden)->toBe(1)
        ->and($parada->tarifa_mano_obra_hora)->toBe('180.0000')
        ->and($parada->costo_maquina_hora)->toBe('220.0000');
});

it('acepta un recorrido ajustado que repite un área', function () {
    entrarALotesComo(Rol::Administrador);
    $producto = productoDeLote();

    postJson('/api/lotes', [
        'producto_id' => $producto->id,
        'cantidad_objetivo' => 10,
        'recorrido' => idsDeAreas(['MOL', 'FUS', 'COL', 'DES', 'CAL', 'GRA', 'CAL']),
    ])
        ->assertCreated()
        ->assertJsonCount(7, 'paradas')
        ->assertJsonPath('paradas.6.area.codigo', 'CAL');
});

it('rechaza un recorrido sin las áreas obligatorias en su orden', function (array $codigos) {
    entrarALotesComo(Rol::Administrador);
    $producto = productoDeLote();

    postJson('/api/lotes', [
        'producto_id' => $producto->id,
        'cantidad_objetivo' => 10,
        'recorrido' => idsDeAreas($codigos),
    ])->assertUnprocessable()->assertJsonValidationErrors(['recorrido']);

    expect(Lote::query()->count())->toBe(0);
})->with([
    'falta una obligatoria' => [['MOL', 'FUS', 'DES', 'CAL']],
    'obligatorias fuera de orden' => [['MOL', 'COL', 'FUS', 'DES']],
    'vacío' => [[]],
]);

it('rechaza un recorrido con un área inactiva', function () {
    entrarALotesComo(Rol::Administrador);
    $producto = productoDeLote();
    areaDeLote('GRA')->update(['activo' => false]);

    postJson('/api/lotes', [
        'producto_id' => $producto->id,
        'cantidad_objetivo' => 10,
        'recorrido' => idsDeAreas(['MOL', 'FUS', 'COL', 'DES', 'GRA']),
    ])->assertUnprocessable()->assertJsonValidationErrors(['recorrido']);
});

it('asigna la materia prima con su costo congelado y la descuenta del inventario', function () {
    $admin = entrarALotesComo(Rol::Administrador);
    $producto = productoDeLote();

    postJson('/api/lotes', ['producto_id' => $producto->id, 'cantidad_objetivo' => 10])
        ->assertCreated()
        ->assertJsonCount(3, 'insumos');

    $lote = Lote::query()->firstOrFail();
    $chatarra = insumoDeLote('CHT-FUN');
    $asignado = $lote->insumos()->where('insumo_id', $chatarra->id)->firstOrFail();

    expect($asignado->cantidad)->toBe('100.000')
        ->and($asignado->costo_unitario)->toBe('7.0000')
        ->and($chatarra->fresh()?->existencia)->toBe('900.000')
        ->and(insumoDeLote('FES-75')->existencia)->toBe('45.000')
        ->and(insumoDeLote('ARE-VER')->existencia)->toBe('4800.000');

    $movimiento = MovimientoInsumo::query()
        ->where('insumo_id', $chatarra->id)
        ->where('tipo', TipoMovimientoInsumo::Asignacion)
        ->firstOrFail();

    expect($movimiento->cantidad)->toBe('-100.000')
        ->and($movimiento->lote_id)->toBe($lote->id)
        ->and($movimiento->user_id)->toBe($admin->id);

    $chatarra->update(['costo_unitario' => 15]);
    expect($asignado->fresh()?->costo_unitario)->toBe('7.0000');
});

it('no crea nada si falta existencia de un insumo', function () {
    entrarALotesComo(Rol::Administrador);
    $producto = productoDeLote();
    insumoDeLote('CHT-FUN')->update(['existencia' => 100]);

    postJson('/api/lotes', ['producto_id' => $producto->id, 'cantidad_objetivo' => 11])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['cantidad_objetivo']);

    expect(Lote::query()->count())->toBe(0)
        ->and(MovimientoInsumo::query()->count())->toBe(0)
        ->and(insumoDeLote('CHT-FUN')->existencia)->toBe('100.000')
        ->and(insumoDeLote('FES-75')->existencia)->toBe('50.000');
});

it('rechaza un lote cuyo metal no cabe en una colada', function () {
    entrarALotesComo(Rol::Administrador);
    $producto = productoDeLote();
    insumoDeLote('CHT-FUN')->update(['existencia' => 10000]);
    insumoDeLote('FES-75')->update(['existencia' => 10000]);
    insumoDeLote('ARE-VER')->update(['existencia' => 100000]);

    postJson('/api/lotes', ['producto_id' => $producto->id, 'cantidad_objetivo' => 72])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['cantidad_objetivo']);

    postJson('/api/lotes', ['producto_id' => $producto->id, 'cantidad_objetivo' => 71])
        ->assertCreated();
});

it('rechaza un producto inactivo o una cantidad inválida', function (array $cambios, string $campo) {
    entrarALotesComo(Rol::Administrador);
    $producto = productoDeLote();
    $producto->update(['activo' => $cambios['activo'] ?? true]);

    postJson('/api/lotes', [
        'producto_id' => $producto->id,
        'cantidad_objetivo' => $cambios['cantidad_objetivo'] ?? 10,
    ])->assertUnprocessable()->assertJsonValidationErrors([$campo]);
})->with([
    'producto inactivo' => [['activo' => false], 'producto_id'],
    'cantidad en cero' => [['cantidad_objetivo' => 0], 'cantidad_objetivo'],
]);

it('solo el administrador crea lotes', function (Rol $rol) {
    entrarALotesComo($rol);
    $producto = productoDeLote();

    postJson('/api/lotes', ['producto_id' => $producto->id, 'cantidad_objetivo' => 10])
        ->assertForbidden();
})->with([Rol::Ventas, Rol::Encargado]);

it('lista los lotes y muestra el detalle por código', function () {
    entrarALotesComo(Rol::Administrador);
    $producto = productoDeLote();
    postJson('/api/lotes', ['producto_id' => $producto->id, 'cantidad_objetivo' => 10])->assertCreated();

    getJson('/api/lotes')
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonPath('0.codigo', 'LOT-20261102-01');

    getJson('/api/lotes/LOT-20261102-01')
        ->assertOk()
        ->assertJsonPath('producto.codigo', 'DIS-FRE-G')
        ->assertJsonCount(6, 'paradas')
        ->assertJsonCount(3, 'insumos');

    getJson('/api/lotes/LOT-20261102-99')->assertNotFound();
});

it('filtra los lotes activos o con movimiento de hoy', function () {
    entrarALotesComo(Rol::Administrador);
    $producto = productoDeLote();
    $datos = ['producto_id' => $producto->id, 'cantidad_objetivo' => 1];

    postJson('/api/lotes', $datos)->assertCreated();
    postJson('/api/lotes', $datos)->assertCreated();
    Lote::query()->where('codigo', 'LOT-20261102-01')->update(['estado' => 'terminado', 'updated_at' => now()->subDays(3)]);
    Lote::query()->where('codigo', 'LOT-20261102-02')->update(['estado' => 'por_confirmar']);

    getJson('/api/lotes?activos=1')
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonPath('0.codigo', 'LOT-20261102-02');

    getJson('/api/lotes')->assertJsonCount(2);
});
