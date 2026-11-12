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
            'usuario' => $this->whenLoaded('usuario', fn () => [
                'id' => $this->usuario->id,
                'name' => $this->usuario->name,
            ]),
            'parada' => $this->whenLoaded('loteArea', fn () => [
                'id' => $this->loteArea->id,
                'orden' => $this->loteArea->orden,
                'area' => [
                    'id' => $this->loteArea->area->id,
                    'codigo' => $this->loteArea->area->codigo,
                    'nombre' => $this->loteArea->area->nombre,
                ],
            ]),
            'insumo' => $this->whenLoaded('loteInsumo', fn () => [
                'lote_insumo_id' => $this->loteInsumo->id,
                'insumo_id' => $this->loteInsumo->insumo_id,
                'codigo' => $this->loteInsumo->insumo->codigo,
                'nombre' => $this->loteInsumo->insumo->nombre,
                'unidad' => $this->loteInsumo->insumo->unidad,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
