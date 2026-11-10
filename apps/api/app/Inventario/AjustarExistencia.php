<?php

namespace App\Inventario;

use App\Enums\TipoMovimientoInsumo;
use App\Models\Ajuste;
use App\Models\Insumo;
use App\Models\MovimientoInsumo;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AjustarExistencia
{
    /**
     * @param  array<string, mixed>  $datos
     * @return array{ajuste: Ajuste, movimiento: MovimientoInsumo}
     */
    public function ejecutar(Insumo $insumo, array $datos, int $usuarioId): array
    {
        return DB::transaction(function () use ($insumo, $datos, $usuarioId): array {
            $insumo = Insumo::query()->lockForUpdate()->findOrFail($insumo->id);
            $anterior = (float) $insumo->existencia;
            $nuevo = (float) $datos['cantidad_real'];

            // Un ajuste que no cambia nada solo ensucia el historial con un
            // movimiento de cero; la existencia ya está en la cantidad real.
            if (abs($nuevo - $anterior) < 0.0005) {
                throw ValidationException::withMessages([
                    'cantidad_real' => 'La cantidad real ya es la existencia actual: no hay nada que ajustar.',
                ]);
            }

            $diferencia = $nuevo - $anterior;

            $ajuste = Ajuste::query()->create([
                'insumo_id' => $insumo->id,
                'campo' => 'existencia',
                'valores' => [
                    'anterior' => $anterior,
                    'nuevo' => $nuevo,
                ],
                'motivo' => $datos['motivo'],
                'user_id' => $usuarioId,
            ]);

            $movimiento = $insumo->movimientos()->create([
                'tipo' => TipoMovimientoInsumo::Ajuste,
                'cantidad' => $diferencia,
                'motivo' => $datos['motivo'],
                'user_id' => $usuarioId,
                'ocurrido_en' => now(),
            ]);

            $insumo->update(['existencia' => $nuevo]);

            (new DetectorStockMinimo)->revisar(
                $insumo->id,
                $anterior,
                $nuevo,
                (float) $insumo->stock_minimo,
            );

            return compact('ajuste', 'movimiento');
        });
    }
}
