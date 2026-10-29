<?php

namespace App\Http\Resources;

use App\Models\Area;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UsuarioResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'rol' => $this->getRoleNames()->first(),
            'activo' => $this->activo,
            'areas' => $this->whenLoaded('areas', fn () => $this->areas->map(fn (Area $area) => [
                'id' => $area->id,
                'codigo' => $area->codigo,
                'nombre' => $area->nombre,
            ])->values()),
        ];
    }
}
