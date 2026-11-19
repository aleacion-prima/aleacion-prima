<?php

use App\Bitacora\Bitacora;
use App\Enums\Rol;
use App\Enums\TipoEventoBitacora;
use App\Inventario\AjustarExistencia;
use App\Lotes\CapturaParada;
use App\Lotes\CierreLote;
use App\Lotes\CreadorLote;
use App\Models\Area;
use App\Models\Insumo;
use App\Models\Lote;
use App\Models\LoteArea;
use App\Models\Producto;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\AreaSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\seed;
use function Pest\Laravel\travelTo;

beforeEach(function () {
    seed([RolesSeeder::class, AreaSeeder::class]);
    travelTo('2026-11-02 09:00:00');
});

function adminBitacora(): User
{
    $admin = User::factory()->create();
    $admin->assignRole(Rol::Administrador);

    return $admin;
}

function insumoRetornoBitacora(): Insumo
{
    return Insumo::query()->where('codigo', config('planta.insumo_retorno'))->first()
        ?? Insumo::factory()->create([
            'codigo' => config('planta.insumo_retorno'),
            'categoria' => 'metal',
            'unidad' => 'kg',
            'costo_unitario' => 7,
            'existencia' => 1000,
            'stock_minimo' => 5,
        ]);
}

function loteBitacora(User $admin): Lote
{
    $producto = Producto::factory()->create();

    foreach (['MOL', 'FUS', 'COL', 'DES'] as $indice => $codigo) {
        $producto->areas()->attach(Area::query()->where('codigo', $codigo)->value('id'), ['orden' => $indice + 1]);
    }

    $producto->insumos()->attach(insumoRetornoBitacora()->id, ['cantidad_por_pieza' => 10]);

    return app(CreadorLote::class)->crear($producto, 20, null, $admin);
}

function paradaBitacora(Lote $lote, int $orden): LoteArea
{
    return $lote->paradas()->where('orden', $orden)->firstOrFail();
}

it('deja creacion y asignacion_insumo al crear el lote, en ese orden', function () {
    $admin = adminBitacora();
    $fake = Bitacora::fake();

    loteBitacora($admin);

    expect($fake->tipos())->toBe(['creacion', 'asignacion_insumo']);
    $fake->assertRegistrado(
        TipoEventoBitacora::Creacion,
        fn (array $doc) => $doc['lote_id'] !== null && $doc['user_id'] === $admin->id && $doc['datos']['cantidad_objetivo'] === 20,
    );
});

it('deja un evento por cada operación del recorrido, en orden', function () {
    $admin = adminBitacora();
    $fake = Bitacora::fake();

    $lote = loteBitacora($admin);
    $captura = app(CapturaParada::class);

    foreach ([1, 2, 3] as $orden) {
        $captura->entrada($lote, paradaBitacora($lote, $orden), 20, null, $admin);
        $captura->salida($lote, paradaBitacora($lote, $orden)->fresh(), 20, null, null, null, $admin);
    }

    // La salida de Desmolde (orden 4) captura retorno de metal.
    $captura->entrada($lote, paradaBitacora($lote, 4), 20, null, $admin);
    $captura->salida($lote, paradaBitacora($lote, 4)->fresh(), 19, null, '35.5', null, $admin);

    app(CierreLote::class)->confirmar($lote->fresh(), $admin);

    expect($fake->tipos())->toBe([
        'creacion',
        'asignacion_insumo',
        'entrada_area',
        'salida_area',
        'entrada_area',
        'salida_area',
        'entrada_area',
        'salida_area',
        'entrada_area',
        'salida_area',
        'retorno',
        'confirmacion',
    ]);
});

it('deja devolucion_insumo y cancelacion al cancelar con devolución', function () {
    $admin = adminBitacora();
    $lote = loteBitacora($admin);
    $chatarra = insumoRetornoBitacora();

    $fake = Bitacora::fake();

    app(CierreLote::class)->cancelar(
        $lote,
        'Pedido cancelado',
        [['insumo_id' => $chatarra->id, 'cantidad' => '100']],
        $admin,
    );

    expect($fake->tipos())->toBe(['devolucion_insumo', 'cancelacion']);
    $fake->assertRegistrado(
        TipoEventoBitacora::Cancelacion,
        fn (array $doc) => $doc['datos']['motivo'] === 'Pedido cancelado',
    );
});

it('deja ajuste al corregir una existencia', function () {
    $admin = adminBitacora();
    $insumo = Insumo::factory()->create(['existencia' => 100, 'stock_minimo' => 5]);

    $fake = Bitacora::fake();

    app(AjustarExistencia::class)->ejecutar($insumo, ['cantidad_real' => 90, 'motivo' => 'Conteo físico'], $admin->id);

    expect($fake->tipos())->toBe(['ajuste']);
    $fake->assertRegistrado(
        TipoEventoBitacora::Ajuste,
        fn (array $doc) => $doc['lote_id'] === null && $doc['datos']['campo'] === 'existencia',
    );
});

it('deja stock_minimo cuando un ajuste cruza el mínimo', function () {
    $admin = adminBitacora();
    $insumo = Insumo::factory()->create(['existencia' => 10, 'stock_minimo' => 5]);

    $fake = Bitacora::fake();

    app(AjustarExistencia::class)->ejecutar($insumo, ['cantidad_real' => 4, 'motivo' => 'Faltante'], $admin->id);

    expect($fake->tipos())->toBe(['ajuste', 'stock_minimo']);
    $fake->assertRegistrado(
        TipoEventoBitacora::StockMinimo,
        fn (array $doc) => $doc['lote_id'] === null && $doc['area_id'] === null && $doc['datos']['insumo_id'] === $insumo->id,
    );
});

it('marca captura_tardia según el umbral', function () {
    $admin = adminBitacora();
    $lote = loteBitacora($admin);
    $captura = app(CapturaParada::class);

    $fake = Bitacora::fake();

    // Entrada a tiempo (ocurrido = registrado = ahora): no es tardía.
    $captura->entrada($lote, paradaBitacora($lote, 1), 20, null, $admin);

    // Se captura la salida casi 2 h después de que ocurrió (> 30 min de umbral): tardía.
    travelTo('2026-11-02 11:00:00');
    $captura->salida($lote, paradaBitacora($lote, 1)->fresh(), 20, null, null, CarbonImmutable::parse('2026-11-02 09:05'), $admin);

    $fake->assertRegistrado(
        TipoEventoBitacora::EntradaArea,
        fn (array $doc) => $doc['captura_tardia'] === false,
    );
    $fake->assertRegistrado(
        TipoEventoBitacora::SalidaArea,
        fn (array $doc) => $doc['captura_tardia'] === true,
    );
});

it('no revierte la operación ni lanza excepción si la bitácora falla', function () {
    Log::spy();
    $admin = adminBitacora();

    Bitacora::fake()->fallar();

    $lote = loteBitacora($admin);

    // El lote y el descuento de inventario quedaron en Postgres pese al fallo.
    assertDatabaseHas('lotes', ['codigo' => $lote->codigo]);
    expect(insumoRetornoBitacora()->fresh()?->existencia)->toBe('800.000');

    Log::shouldHaveReceived('error')
        ->withArgs(fn (string $mensaje) => $mensaje === 'No se pudo escribir en la bitácora');
});

it('un rollback al cancelar no deja devolucion_insumo ni cancelacion', function () {
    $admin = adminBitacora();
    $lote = loteBitacora($admin);
    $chatarra = insumoRetornoBitacora();

    $fake = Bitacora::fake();

    // La primera devolución es válida (registra devolucion_insumo); la segunda
    // excede lo que queda del insumo y lanza dentro de la transacción.
    expect(fn () => app(CierreLote::class)->cancelar(
        $lote,
        'Pedido cancelado',
        [
            ['insumo_id' => $chatarra->id, 'cantidad' => '100'],
            ['insumo_id' => $chatarra->id, 'cantidad' => '200'],
        ],
        $admin,
    ))->toThrow(ValidationException::class);

    // Como la cancelación se revirtió, el after-commit se descarta: ni la
    // devolución ya registrada ni la cancelación llegan a la bitácora.
    expect($fake->tipos())->not->toContain('devolucion_insumo');
    $fake->assertNingunRegistro();
});

it('una salida que falla en registrarRetorno no deja salida_area', function () {
    $admin = adminBitacora();
    $lote = loteBitacora($admin);
    $captura = app(CapturaParada::class);

    // Avanza el lote hasta la entrada de Desmolde (orden 4).
    foreach ([1, 2, 3] as $orden) {
        $captura->entrada($lote, paradaBitacora($lote, $orden), 20, null, $admin);
        $captura->salida($lote, paradaBitacora($lote, $orden)->fresh(), 20, null, null, null, $admin);
    }
    $captura->entrada($lote, paradaBitacora($lote, 4), 20, null, $admin);

    // El insumo de retorno "desaparece" (cambia de código): validarRetorno pasa
    // (sigue contando como metal asignado) pero registrarRetorno no lo encuentra
    // y lanza, cuando salida_area ya se registró.
    insumoRetornoBitacora()->update(['codigo' => 'NO-RETORNO']);

    $fake = Bitacora::fake();

    expect(fn () => $captura->salida(
        $lote,
        paradaBitacora($lote, 4)->fresh(),
        19,
        null,
        '35.5',
        null,
        $admin,
    ))->toThrow(ValidationException::class);

    // La salida se revirtió: su salida_area (y el retorno) no quedan registrados.
    expect($fake->tipos())->not->toContain('salida_area');
    $fake->assertNingunRegistro();
});

it('al cancelar con una parada abierta deja su area y merma en la cancelacion', function () {
    $admin = adminBitacora();
    $lote = loteBitacora($admin);
    $captura = app(CapturaParada::class);

    // Entra a la primera parada: queda abierta cuando se cancela.
    $parada = $captura->entrada($lote, paradaBitacora($lote, 1), 20, null, $admin);

    $fake = Bitacora::fake();

    app(CierreLote::class)->cancelar($lote, 'Pedido cancelado', [], $admin);

    $fake->assertRegistrado(
        TipoEventoBitacora::Cancelacion,
        fn (array $doc) => $doc['datos']['area_id'] === $parada->area_id
            && $doc['datos']['merma_piezas'] === 20,
    );
});
