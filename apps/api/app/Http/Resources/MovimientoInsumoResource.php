<?php

namespace App\Http\Resources;

use App\Models\MovimientoInsumo;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin MovimientoInsumo
 */
class MovimientoInsumoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'insumo_id' => $this->insumo_id,
            'tipo' => $this->tipo,
            'cantidad' => $this->cantidad,
            'costo_unitario' => $this->costo_unitario,
            'proveedor' => $this->proveedor,
            'lote_proveedor' => $this->lote_proveedor,
            'user_id' => $this->user_id,
            'ocurrido_en' => $this->ocurrido_en ?: null,
        ];
    }
}
