<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Serializa el arreglo uniforme que arma el servicio SesionesActivas (una sesión
 * del panel o un token de la app) al JSON de la API.
 */
class SesionActivaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array{id: string, tipo: string, dispositivo: string, ip: string|null, ultima_actividad: string|null, actual: bool} $sesion */
        $sesion = $this->resource;

        return [
            'id' => $sesion['id'],
            'tipo' => $sesion['tipo'],
            'dispositivo' => $sesion['dispositivo'],
            'ip' => $sesion['ip'],
            'ultima_actividad' => $sesion['ultima_actividad'],
            'actual' => $sesion['actual'],
        ];
    }
}
