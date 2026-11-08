<?php

namespace App\Http\Resources;

use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Producto
 */
class ProductoInventarioResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $especificaciones = is_array($this->especificaciones) ? $this->especificaciones : [];

        return [
            'id' => $this->id,
            'codigo' => $this->codigo,
            'nombre' => $this->nombre,
            // Las especificaciones son un jsonb libre: no todos los productos
            // traen material o peso, así que se leen sin asumir su presencia.
            'material' => $this->texto($especificaciones['material'] ?? null),
            'peso' => $this->texto($especificaciones['peso_kg'] ?? null),
            'existencia' => $this->existencia,
            'activo' => $this->activo,
        ];
    }

    private function texto(mixed $valor): ?string
    {
        return is_string($valor) || is_numeric($valor) ? (string) $valor : null;
    }
}
