<?php

namespace App\Http\Resources;

use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Producto
 */
class ProductoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'codigo' => $this->codigo,
            'nombre' => $this->nombre,
            'especificaciones' => (object) ($this->especificaciones ?? []),
            'precio_mayoreo' => $this->precio_mayoreo,
            'precio_menudeo' => $this->precio_menudeo,
            'existencia' => $this->existencia,
            'publicado' => $this->publicado,
            'activo' => $this->activo,
            'recorrido' => $this->areas->map(fn ($area) => [
                'id' => $area->id,
                'codigo' => $area->codigo,
                'nombre' => $area->nombre,
                'obligatoria' => $area->obligatoria,
                'orden' => (int) data_get($area, 'pivot.orden'),
            ])->values(),
            'receta' => $this->insumos->map(fn ($insumo) => [
                'insumo_id' => $insumo->id,
                'codigo' => $insumo->codigo,
                'nombre' => $insumo->nombre,
                'unidad' => $insumo->unidad,
                'categoria' => $insumo->categoria,
                'cantidad_por_pieza' => (float) data_get($insumo, 'pivot.cantidad_por_pieza'),

            ])->values(),
        ];
    }
}
