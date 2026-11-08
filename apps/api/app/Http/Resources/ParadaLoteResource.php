<?php

namespace App\Http\Resources;

use App\Enums\Rol;
use App\Models\LoteArea;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin LoteArea
 */
class ParadaLoteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $veCostos = (bool) $request->user()?->hasAnyRole([Rol::Administrador, Rol::Ventas]);

        return [
            'id' => $this->id,
            'orden' => $this->orden,
            'area' => $this->whenLoaded('area', fn () => [
                'id' => $this->area->id,
                'codigo' => $this->area->codigo,
                'nombre' => $this->area->nombre,
            ]),
            'tarifa_mano_obra_hora' => $this->when($veCostos, $this->tarifa_mano_obra_hora),
            'costo_maquina_hora' => $this->when($veCostos, $this->costo_maquina_hora),
            'piezas_recibidas' => $this->piezas_recibidas,
            'piezas_entregadas' => $this->piezas_entregadas,
            'merma_piezas' => $this->merma_piezas,
            'entrada_ocurrido_en' => $this->entrada_ocurrido_en,
            'salida_ocurrido_en' => $this->salida_ocurrido_en,
        ];
    }
}
