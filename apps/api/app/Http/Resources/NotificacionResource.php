<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Notifications\DatabaseNotification;

/**
 * Serializa una notificación de la base de datos a la carga mínima de la API.
 *
 * @mixin DatabaseNotification
 */
class NotificacionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tipo' => $this->data['tipo'] ?? null,
            'mensaje' => $this->data['mensaje'] ?? null,
            'codigo' => $this->data['codigo'] ?? null,
            'leida' => $this->read_at !== null,
            'fecha' => $this->created_at?->toISOString(),
        ];
    }
}
