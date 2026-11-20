<?php

namespace App\Http\Resources;

use App\Support\Modulos;
use Illuminate\Http\Request;

/**
 * Datos de la sesión del panel: el usuario más el estado de los módulos.
 * Solo lo usa GET /api/user; la lista de administración sigue con UsuarioResource.
 */
class SesionResource extends UsuarioResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'modulos' => Modulos::estado(),
        ];
    }
}
