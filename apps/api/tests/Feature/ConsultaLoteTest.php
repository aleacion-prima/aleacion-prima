<?php

use App\Bitacora\LectorBitacoraFake;
use App\Enums\EstadoLote;
use App\Enums\Rol;
use App\Enums\TipoMovimientoInsumo;
use App\Lotes\CalculadorCostoLote;
use App\Models\Area;
use App\Models\Insumo;
use App\Models\Lote;
use App\Models\LoteArea;
use App\Models\LoteInsumo;
use App\Models\MovimientoInsumo;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;
use function Pest\Laravel\seed;

beforeEach(function () {
    seed(RolesSeeder::class);
});

function consultarComo(Rol $rol): User
{
    $usuario = User::factory()->create();
    $usuario->assignRole($rol);
    Sanctum::actingAs($usuario, ['*']);

    return $usuario;
}

/** Dos paradas capturadas por personas distintas, con una captura tardía y una discrepancia. */
function loteConCapturas(): Lote
{
    $lote = Lote::factory()->enProceso()->create();
    $ana = User::factory()->create(['name' => 'Ana Pérez']);
    $beto = User::factory()->create(['name' => 'Beto Ruiz']);

    LoteArea::factory()->create([
        'lote_id' => $lote->id,
        'area_id' => Area::factory()->create(['codigo' => 'MOL', 'nombre' => 'Moldeo'])->id,
        'orden' => 1,
        'piezas_recibidas' => 20,
        'piezas_entregadas' => 19,
        'merma_piezas' => 1,
        'entrada_ocurrido_en' => '2026-11-02 06:00',
        'entrada_registrado_en' => '2026-11-03 09:00',
        'entrada_user_id' => $ana->id,
        'salida_ocurrido_en' => '2026-11-02 06:30',
        'salida_registrado_en' => '2026-11-02 06:31',
        'salida_user_id' => $beto->id,
    ]);

    LoteArea::factory()->create([
        'lote_id' => $lote->id,
        'area_id' => Area::factory()->create(['codigo' => 'FUS', 'nombre' => 'Fusión'])->id,
        'orden' => 2,
        'piezas_recibidas' => 17,
        'entrada_ocurrido_en' => '2026-11-02 06:35',
        'entrada_registrado_en' => '2026-11-02 06:36',
        'entrada_user_id' => $beto->id,
    ]);

    return $lote;
}

/** LOT-20260924-01 de docs/empresa.md (sección 8), con su costo ya calculado. */
function loteReferenciaParaApi(): Lote
{
    $lote = Lote::factory()->create([
        'codigo' => 'LOT-20260924-01',
        'estado' => EstadoLote::Terminado,
        'cantidad_objetivo' => 50,
        'piezas_terminadas' => 46,
    ]);

    $insumos = [];
    foreach ([
        ['CHT-FUN', '278.5', '7.00'], ['CHT-102', '278.5', '9.50'], ['REC-GRA', '14.5', '30.00'],
        ['FES-75', '8.5', '40.00'], ['ARE-VER', '600', '1.20'],
    ] as [$codigo, $cantidad, $costo]) {
        $insumos[$codigo] = Insumo::factory()->create(['codigo' => $codigo, 'costo_unitario' => $costo]);
        LoteInsumo::factory()->create([
            'lote_id' => $lote->id, 'insumo_id' => $insumos[$codigo]->id,
            'cantidad' => $cantidad, 'costo_unitario' => $costo, 'cantidad_devuelta' => '0',
        ]);
    }

    foreach ([
        ['MOL', '2026-09-24 07:00', '2026-09-24 10:30', '180', '220'],
        ['FUS', '2026-09-24 10:30', '2026-09-24 12:30', '220', '450'],
        ['COL', '2026-09-24 12:30', '2026-09-24 17:00', '60', '80'],
        ['DES', '2026-09-25 07:00', '2026-09-25 08:30', '160', '180'],
        ['GRA', '2026-09-25 08:30', '2026-09-25 10:30', '170', '200'],
        ['CAL', '2026-09-25 10:30', '2026-09-25 12:00', '250', '300'],
    ] as $orden => [$codigo, $entrada, $salida, $manoObra, $maquina]) {
        LoteArea::factory()->create([
            'lote_id' => $lote->id, 'area_id' => Area::factory()->create(['codigo' => $codigo])->id,
            'orden' => $orden + 1, 'tarifa_mano_obra_hora' => $manoObra, 'costo_maquina_hora' => $maquina,
            'entrada_ocurrido_en' => $entrada, 'salida_ocurrido_en' => $salida,
        ]);
    }

    MovimientoInsumo::create([
        'insumo_id' => $insumos['CHT-FUN']->id, 'tipo' => TipoMovimientoInsumo::Retorno,
        'cantidad' => '162', 'costo_unitario' => '7.00', 'lote_id' => $lote->id,
        'user_id' => User::factory()->create()->id, 'ocurrido_en' => '2026-09-25 08:30',
    ]);

    return app(CalculadorCostoLote::class)->recalcular($lote)->fresh();
}

/** Lote abierto: 90 × $7 − 20 × $7 de material, 2.5 h a $100 y a $50. */
function loteAbiertoConCosto(): Lote
{
    $lote = Lote::factory()->enProceso()->create(['piezas_terminadas' => null]);
    $insumo = Insumo::factory()->create(['codigo' => 'CHT-FUN', 'costo_unitario' => '7.00']);
    LoteInsumo::factory()->create([
        'lote_id' => $lote->id, 'insumo_id' => $insumo->id,
        'cantidad' => '100', 'costo_unitario' => '7.00', 'cantidad_devuelta' => '10',
    ]);
    MovimientoInsumo::create([
        'insumo_id' => $insumo->id, 'tipo' => TipoMovimientoInsumo::Retorno, 'cantidad' => '20',
        'costo_unitario' => '7.00', 'lote_id' => $lote->id,
        'user_id' => User::factory()->create()->id, 'ocurrido_en' => '2026-09-25 08:30',
    ]);
    LoteArea::factory()->create([
        'lote_id' => $lote->id, 'area_id' => Area::factory()->create()->id, 'orden' => 1,
        'tarifa_mano_obra_hora' => '100', 'costo_maquina_hora' => '50',
        'entrada_ocurrido_en' => '2026-09-25 08:00', 'salida_ocurrido_en' => '2026-09-25 10:30',
    ]);

    return app(CalculadorCostoLote::class)->recalcular($lote)->fresh();
}

describe('historial', function () {
    it('lista las paradas en orden con quién capturó, la captura tardía y la discrepancia', function () {
        $lote = loteConCapturas();
        consultarComo(Rol::Administrador);

        getJson("/api/lotes/{$lote->codigo}/historial")
            ->assertOk()
            ->assertJsonPath('codigo', $lote->codigo)
            ->assertJsonCount(2, 'paradas')
            ->assertJsonPath('paradas.0.area.codigo', 'MOL')
            ->assertJsonPath('paradas.0.entrada_por', 'Ana Pérez')
            ->assertJsonPath('paradas.0.salida_por', 'Beto Ruiz')
            ->assertJsonPath('paradas.0.merma_piezas', 1)
            ->assertJsonPath('paradas.0.entrada_tardia', true)
            ->assertJsonPath('paradas.0.salida_tardia', false)
            ->assertJsonPath('paradas.1.area.codigo', 'FUS')
            ->assertJsonPath('paradas.1.entrada_por', 'Beto Ruiz')
            ->assertJsonPath('paradas.1.salida_por', null)
            ->assertJsonPath('paradas.1.discrepancia', -2);
    });

    it('lo ven administrador, ventas y encargado', function (Rol $rol) {
        $lote = loteConCapturas();
        consultarComo($rol);

        getJson("/api/lotes/{$lote->codigo}/historial")->assertOk();
    })->with([Rol::Administrador, Rol::Ventas, Rol::Encargado]);

    it('no entrega tarifas al encargado', function () {
        $lote = loteConCapturas();
        consultarComo(Rol::Encargado);

        getJson("/api/lotes/{$lote->codigo}/historial")
            ->assertOk()
            ->assertJsonMissingPath('paradas.0.tarifa_mano_obra_hora')
            ->assertJsonMissingPath('paradas.0.costo_maquina_hora');
    });

    it('responde 403 al cliente y 404 a un lote que no existe', function () {
        $lote = loteConCapturas();
        consultarComo(Rol::Cliente);

        getJson("/api/lotes/{$lote->codigo}/historial")->assertForbidden();

        consultarComo(Rol::Administrador);

        getJson('/api/lotes/LOT-00000000-99/historial')->assertNotFound();
    });

    describe('sin sesión', function () {
        it('responde 401', function () {
            $lote = loteConCapturas();

            getJson("/api/lotes/{$lote->codigo}/historial")->assertUnauthorized();
        });
    });
});

describe('bitácora', function () {
    it('lista los eventos del lote en orden cronológico, con área y quién los registró', function (Rol $rol) {
        $lote = loteConCapturas();
        $otro = Lote::factory()->create();
        $area = Area::query()->where('codigo', 'MOL')->firstOrFail();
        $ana = User::query()->where('name', 'Ana Pérez')->firstOrFail();

        LectorBitacoraFake::con([
            [
                'lote_id' => $lote->id, 'area_id' => $area->id, 'user_id' => $ana->id,
                'tipo' => 'salida_area', 'descripcion' => 'Salida de Moldeo: 19 piezas, merma 1.',
                'datos' => ['piezas_entregadas' => 19],
                'ocurrido_en' => '2026-11-02 06:30', 'registrado_en' => '2026-11-02 06:31', 'captura_tardia' => false,
            ],
            [
                'lote_id' => $lote->id, 'area_id' => $area->id, 'user_id' => $ana->id,
                'tipo' => 'entrada_area', 'descripcion' => 'Entrada a Moldeo: 20 piezas.',
                'datos' => ['piezas_recibidas' => 20],
                'ocurrido_en' => '2026-11-02 06:00', 'registrado_en' => '2026-11-03 09:00', 'captura_tardia' => true,
            ],
            [
                'lote_id' => $otro->id, 'area_id' => null, 'user_id' => null,
                'tipo' => 'creacion', 'descripcion' => 'Creación de otro lote.', 'datos' => [],
                'ocurrido_en' => '2026-11-02 05:00', 'registrado_en' => '2026-11-02 05:00', 'captura_tardia' => false,
            ],
        ]);

        consultarComo($rol);

        getJson("/api/lotes/{$lote->codigo}/bitacora")
            ->assertOk()
            ->assertJsonCount(2)
            ->assertJsonPath('0.tipo', 'entrada_area')
            ->assertJsonPath('0.captura_tardia', true)
            ->assertJsonPath('0.usuario', 'Ana Pérez')
            ->assertJsonPath('0.area.codigo', 'MOL')
            ->assertJsonPath('1.tipo', 'salida_area')
            ->assertJsonPath('1.datos.piezas_entregadas', 19)
            ->assertJsonPath('1.captura_tardia', false);
    })->with([Rol::Administrador, Rol::Ventas]);

    it('responde 403 al encargado y al cliente', function (Rol $rol) {
        $lote = loteConCapturas();
        consultarComo($rol);

        getJson("/api/lotes/{$lote->codigo}/bitacora")->assertForbidden();
    })->with([Rol::Encargado, Rol::Cliente]);

    describe('sin sesión', function () {
        it('responde 401', function () {
            $lote = loteConCapturas();

            getJson("/api/lotes/{$lote->codigo}/bitacora")->assertUnauthorized();
        });
    });
});

describe('costo', function () {
    it('muestra $10,401.25 y $226.11 por pieza en el lote de referencia', function () {
        $lote = loteReferenciaParaApi();
        consultarComo(Rol::Administrador);

        $costo = getJson("/api/lotes/{$lote->codigo}/costo")
            ->assertOk()
            ->assertJsonPath('congelado', true)
            ->assertJsonPath('piezas_terminadas', 46)
            ->assertJsonPath('material', '4956.2500')
            ->assertJsonPath('mano_obra', '2295.0000')
            ->assertJsonPath('maquina', '3150.0000')
            ->assertJsonPath('total', '10401.2500')
            ->json();

        expect(number_format((float) $costo['total'], 2, '.', ','))->toBe('10,401.25')
            ->and(number_format((float) $costo['por_pieza'], 2, '.', ''))->toBe('226.11');
    });

    it('en un lote abierto es el costo a la fecha y no trae costo por pieza', function () {
        $lote = loteAbiertoConCosto();
        consultarComo(Rol::Ventas);

        getJson("/api/lotes/{$lote->codigo}/costo")
            ->assertOk()
            ->assertJsonPath('congelado', false)
            ->assertJsonPath('material', '490.0000')
            ->assertJsonPath('mano_obra', '250.0000')
            ->assertJsonPath('maquina', '125.0000')
            ->assertJsonPath('total', '865.0000')
            ->assertJsonPath('por_pieza', null);
    });

    it('responde 403 al encargado y al cliente', function (Rol $rol) {
        $lote = loteAbiertoConCosto();
        consultarComo($rol);

        getJson("/api/lotes/{$lote->codigo}/costo")->assertForbidden();
    })->with([Rol::Encargado, Rol::Cliente]);

    describe('sin sesión', function () {
        it('responde 401', function () {
            $lote = loteAbiertoConCosto();

            getJson("/api/lotes/{$lote->codigo}/costo")->assertUnauthorized();
        });
    });
});
