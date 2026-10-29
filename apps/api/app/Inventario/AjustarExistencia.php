<?php

namespace App\Inventario;

use App\Enums\TipoMovimientoInsumo;
use App\Models\Ajuste;
use App\Models\Insumo;
use App\Models\MovimientoInsumo;
use Illuminate\Support\Facades\DB;

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

            return compact('ajuste', 'movimiento');
        });
    }
}
