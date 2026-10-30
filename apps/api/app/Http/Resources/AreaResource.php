<?php

namespace App\Http\Resources;

use App\Models\Area;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Area
 */
class AreaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'codigo' => $this->codigo,
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'tarifa_mano_obra_hora' => $this->tarifa_mano_obra_hora,
            'costo_maquina_hora' => $this->costo_maquina_hora,
            'obligatoria' => $this->obligatoria,
            'orden_catalogo' => $this->orden_catalogo,
            'activo' => $this->activo,
        ];
    }
}
