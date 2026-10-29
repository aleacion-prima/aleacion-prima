<?php

namespace App\Http\Resources;

use App\Models\Ajuste;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Ajuste
 */
class AjusteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'insumo_id' => $this->insumo_id,
            'campo' => $this->campo,
            'valores' => $this->valores,
            'motivo' => $this->motivo,
            'user_id' => $this->user_id,
            'movimiento' => new MovimientoInsumoResource($this->whenLoaded('movimiento')),
        ];
    }
}
