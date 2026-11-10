<?php

namespace App\Lotes;

use App\Enums\EstadoLote;
use App\Enums\TipoMovimientoInsumo;
use App\Models\Lote;
use App\Models\LoteArea;
use App\Models\MovimientoInsumo;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Closure;
use LogicException;

class CalculadorCostoLote
{
    private const ESCALA = 4;

    private const SEGUNDOS_POR_HORA = 3600;

    public function recalcular(Lote $lote): Lote
    {
        $material = $this->material($lote);
        $manoObra = $this->porHoras($lote, fn (LoteArea $parada): string => (string) $parada->tarifa_mano_obra_hora);
        $maquina = $this->porHoras($lote, fn (LoteArea $parada): string => (string) $parada->costo_maquina_hora);
        $total = $material->plus($manoObra)->plus($maquina);

        $lote->forceFill([
            'costo_material' => (string) $material,
            'costo_mano_obra' => (string) $manoObra,
            'costo_maquina' => (string) $maquina,
            'costo_total' => (string) $total,
            'costo_por_pieza' => $this->porPieza($lote, $total),
        ])->save();

        return $lote;
    }

    /** Σ (asignado − devuelto) × costo congelado − Σ retornos × su costo. */
    private function material(Lote $lote): BigDecimal
    {
        $asignado = BigDecimal::zero();
        foreach ($lote->insumos()->get() as $renglon) {
            $consumido = BigDecimal::of((string) $renglon->cantidad)->minus((string) ($renglon->cantidad_devuelta ?? '0'));
            $asignado = $asignado->plus($consumido->multipliedBy((string) $renglon->costo_unitario));
        }

        $retornado = BigDecimal::zero();
        $retornos = MovimientoInsumo::query()
            ->where('lote_id', $lote->id)
            ->where('tipo', TipoMovimientoInsumo::Retorno)
            ->get();
        foreach ($retornos as $retorno) {
            if ($retorno->costo_unitario === null) {
                throw new LogicException("El retorno {$retorno->id} no tiene costo unitario.");
            }
            $retornado = $retornado->plus(BigDecimal::of((string) $retorno->cantidad)->multipliedBy((string) $retorno->costo_unitario));
        }

        return $asignado->minus($retornado)->toScale(self::ESCALA, RoundingMode::HalfUp);
    }

    /**
     * @param  Closure(LoteArea): string  $tarifa
     */
    private function porHoras(Lote $lote, Closure $tarifa): BigDecimal
    {
        $acumulado = BigDecimal::zero();
        foreach ($lote->paradas()->get() as $parada) {
            if ($parada->entrada_ocurrido_en === null || $parada->salida_ocurrido_en === null) {
                continue;
            }
            $segundos = $parada->salida_ocurrido_en->getTimestamp() - $parada->entrada_ocurrido_en->getTimestamp();
            $acumulado = $acumulado->plus(BigDecimal::of($tarifa($parada))->multipliedBy((string) $segundos));
        }

        return $acumulado->dividedBy((string) self::SEGUNDOS_POR_HORA, self::ESCALA, RoundingMode::HalfUp);
    }

    private function porPieza(Lote $lote, BigDecimal $total): ?string
    {
        $piezas = $lote->piezas_terminadas ?? 0;
        if ($lote->estado !== EstadoLote::Terminado || $piezas <= 0) {
            return null;
        }

        return (string) $total->dividedBy((string) $piezas, self::ESCALA, RoundingMode::HalfUp);
    }
}
