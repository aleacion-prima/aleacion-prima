<?php

namespace App\Inventario;

use App\Enums\TipoMovimientoInsumo;
use App\Models\Insumo;
use App\Models\MovimientoInsumo;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RegistroEntrada
{
    /**
     * @param  array<string, mixed>  $datos
     */
    public function ejecutar(Insumo $insumo, array $datos, int $usuarioId): MovimientoInsumo
    {
        return DB::transaction(function () use ($insumo, $datos, $usuarioId): MovimientoInsumo {
            $insumo = Insumo::query()->lockForUpdate()->findOrFail($insumo->id);
            $existenciaAnterior = (float) $insumo->existencia;
            $cantidad = (float) $datos['cantidad'];
            $ocurridoEn = isset($datos['ocurrido_en'])
                ? Carbon::parse($datos['ocurrido_en'])
                : now();

            $movimiento = $insumo->movimientos()->create([
                'tipo' => TipoMovimientoInsumo::Entrada,
                'cantidad' => $cantidad,
                'costo_unitario' => $datos['costo_unitario'],
                'proveedor' => $datos['proveedor'],
                'lote_proveedor' => $datos['lote_proveedor'] ?? null,
                'user_id' => $usuarioId,
                'ocurrido_en' => $ocurridoEn,
            ]);

            $insumo->update([
                'existencia' => $existenciaAnterior + $cantidad,
                'costo_unitario' => $datos['costo_unitario'],
            ]);

            (new DetectorStockMinimo)->revisar(
                $insumo->id,
                $existenciaAnterior,
                $existenciaAnterior + $cantidad,
                (float) $insumo->stock_minimo,
            );

            return $movimiento;
        });
    }
}
