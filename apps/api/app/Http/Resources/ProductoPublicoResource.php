<?php

namespace App\Http\Resources;

use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Producto
 */
class ProductoPublicoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'especificaciones' => $this->especificaciones,
            'disponible' => $this->existencia > 0,
            'cantidad' => $this->mostrar_cantidad ? (int) $this->existencia : null,
        ];
    }
}
