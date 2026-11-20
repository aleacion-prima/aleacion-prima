<?php

namespace App\Http\Resources;

use App\Models\Area;
use App\Models\Imprevisto;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * El área y quien registró vienen de Postgres: el controlador los pone como relaciones
 * (`area`, `registrador`) antes de armar la respuesta.
 *
 * @mixin Imprevisto
 */
class ImprevistoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $area = $this->resource->getRelation('area');
        $registrador = $this->resource->getRelation('registrador');

        return [
            'id' => (string) $this->resource->getKey(),
            'lote_id' => $this->lote_id,
            'area' => $area instanceof Area
                ? ['id' => $area->id, 'codigo' => $area->codigo, 'nombre' => $area->nombre]
                : null,
            'tipo' => $this->tipo->value,
            'descripcion' => $this->descripcion,
            'maquina' => $this->maquina,
            'piezas_afectadas' => $this->piezas_afectadas,
            'duracion_minutos' => $this->duracion_minutos,
            'resuelto' => $this->resuelto,
            'registrado_por' => $registrador instanceof User ? $registrador->name : null,
            'ocurrido_en' => $this->ocurrido_en?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
