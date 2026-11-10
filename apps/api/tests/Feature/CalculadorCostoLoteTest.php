<?php

use App\Enums\EstadoLote;
use App\Enums\TipoMovimientoInsumo;
use App\Events\LoteActualizado;
use App\Lotes\CalculadorCostoLote;
use App\Models\Area;
use App\Models\Insumo;
use App\Models\Lote;
use App\Models\LoteArea;
use App\Models\LoteInsumo;
use App\Models\MovimientoInsumo;
use App\Models\User;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

/** LOT-20260924-01 de docs/empresa.md (sección 8). */
function loteDeReferencia(bool $conRetorno = true): Lote
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

    if ($conRetorno) {
        MovimientoInsumo::create([
            'insumo_id' => $insumos['CHT-FUN']->id, 'tipo' => TipoMovimientoInsumo::Retorno,
            'cantidad' => '162', 'costo_unitario' => '7.00', 'lote_id' => $lote->id,
            'user_id' => User::factory()->create()->id, 'ocurrido_en' => '2026-09-25 08:30',
        ]);
    }

    return $lote;
}

function aCentavos(string $importe): string
{
    return (string) BigDecimal::of($importe)->toScale(2, RoundingMode::HalfUp);
}

it('da $10,401.25 y $226.11 por pieza en el lote de referencia (RNF-07)', function () {
    $lote = app(CalculadorCostoLote::class)->recalcular(loteDeReferencia())->fresh();

    expect($lote->costo_material)->toBe('4956.2500')
        ->and($lote->costo_mano_obra)->toBe('2295.0000')
        ->and($lote->costo_maquina)->toBe('3150.0000')
        ->and($lote->costo_total)->toBe('10401.2500')
        ->and(aCentavos($lote->costo_por_pieza))->toBe('226.11');
});

it('sin el retorno el costo por pieza sería $250.77', function () {
    $lote = app(CalculadorCostoLote::class)->recalcular(loteDeReferencia(conRetorno: false))->fresh();

    expect($lote->costo_material)->toBe('6090.2500')
        ->and(aCentavos($lote->costo_por_pieza))->toBe('250.77');
});

it('resta lo devuelto y el retorno, y deja el costo por pieza vacío mientras el lote está abierto', function () {
    $lote = Lote::factory()->create(['estado' => EstadoLote::EnProceso, 'piezas_terminadas' => null]);
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

    $lote = app(CalculadorCostoLote::class)->recalcular($lote)->fresh();

    // (100 − 10) × 7 − 20 × 7 = 490; 2.5 h × 100 = 250; 2.5 h × 50 = 125
    expect($lote->costo_material)->toBe('490.0000')
        ->and($lote->costo_mano_obra)->toBe('250.0000')
        ->and($lote->costo_maquina)->toBe('125.0000')
        ->and($lote->costo_total)->toBe('865.0000')
        ->and($lote->costo_por_pieza)->toBeNull();
});

it('lotes:recalcular deja los mismos números y repara una caché desincronizada', function () {
    $lote = app(CalculadorCostoLote::class)->recalcular(loteDeReferencia())->fresh();
    $esperado = $lote->only(['costo_material', 'costo_mano_obra', 'costo_maquina', 'costo_total', 'costo_por_pieza']);

    $this->artisan('lotes:recalcular')->assertSuccessful();
    expect($lote->fresh()->only(array_keys($esperado)))->toBe($esperado);

    Lote::query()->whereKey($lote->id)->update(['costo_material' => '0', 'costo_total' => '0']);
    $this->artisan('lotes:recalcular')->assertSuccessful();
    expect($lote->fresh()->only(array_keys($esperado)))->toBe($esperado);
});

it('no usa float en el cálculo (RNF-07)', function () {
    $codigo = file_get_contents(app_path('Lotes/CalculadorCostoLote.php'));

    expect($codigo)->not->toMatch('/\(float\)|\(double\)|floatval|round\(/');
});

it('recalcula los costos cuando se emite LoteActualizado', function () {
    $lote = loteDeReferencia();

    LoteActualizado::dispatch($lote);

    expect($lote->fresh()->costo_total)->toBe('10401.2500');
});
