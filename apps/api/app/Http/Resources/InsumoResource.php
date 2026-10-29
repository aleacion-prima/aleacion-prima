<?php

namespace App\Http\Resources;

use App\Models\Insumo;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Insumo
 */
class InsumoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'codigo' => $this->codigo,
            'nombre' => $this->nombre,
            'categoria' => $this->categoria,
            'unidad' => $this->unidad,
            'costo_unitario' => $this->costo_unitario,
            'existencia' => $this->existencia,
            'stock_minimo' => $this->stock_minimo,
            'activo' => $this->activo,
        ];
    }
}
