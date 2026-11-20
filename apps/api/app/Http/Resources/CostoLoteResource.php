<?php

namespace App\Http\Resources;

use App\Enums\EstadoLote;
use App\Models\Lote;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Lote
 */
class CostoLoteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'codigo' => $this->codigo,
            'estado' => $this->estado,
            // Abierto: costo a la fecha. Terminado o cancelado: congelado (ADR-0010).
            'congelado' => in_array($this->estado, [EstadoLote::Terminado, EstadoLote::Cancelado], true),
            'piezas_terminadas' => $this->piezas_terminadas,
            'material' => $this->costo_material,
            'mano_obra' => $this->costo_mano_obra,
            'maquina' => $this->costo_maquina,
            'total' => $this->costo_total,
            'por_pieza' => $this->costo_por_pieza,
        ];
    }
}
