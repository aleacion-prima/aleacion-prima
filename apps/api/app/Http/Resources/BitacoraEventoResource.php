<?php

namespace App\Http\Resources;

use App\Models\Bitacora as EventoBitacora;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin EventoBitacora
 */
class BitacoraEventoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->getKey(),
            'tipo' => $this->tipo,
            'accion' => $this->accion,
            'resultado' => $this->resultado,
            'entidad' => $this->entidad,
            'entidad_id' => $this->entidad_id,
            'descripcion' => $this->descripcion,
            'area' => $this->whenLoaded('area', fn () => [
                'id' => $this->area->id,
                'codigo' => $this->area->codigo,
                'nombre' => $this->area->nombre,
            ]),
            'usuario' => $this->whenLoaded('registrador', fn () => $this->accion === null
                ? $this->registrador->name
                : ['id' => $this->registrador->id, 'nombre' => $this->registrador->name]),
            'datos' => $this->datos ?? [],
            'fecha' => $this->registrado_en->toISOString(),
            'ocurrido_en' => $this->ocurrido_en,
            'registrado_en' => $this->registrado_en,
            'captura_tardia' => (bool) $this->captura_tardia,
        ];
    }
}
