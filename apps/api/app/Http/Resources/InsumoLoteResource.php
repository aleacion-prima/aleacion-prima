<?php

namespace App\Http\Resources;

use App\Models\LoteInsumo;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin LoteInsumo
 */
class InsumoLoteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $veCostos = (bool) $request->user()?->veCostos();

        return [
            'id' => $this->id,
            'insumo' => $this->whenLoaded('insumo', fn () => [
                'id' => $this->insumo->id,
                'codigo' => $this->insumo->codigo,
                'nombre' => $this->insumo->nombre,
                'unidad' => $this->insumo->unidad,
            ]),
            'cantidad' => $this->cantidad,
            'cantidad_devuelta' => $this->cantidad_devuelta,
            'costo_unitario' => $this->when($veCostos, $this->costo_unitario),
        ];
    }
}
