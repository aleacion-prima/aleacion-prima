<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property array<string, mixed>|null $especificaciones
 */
#[Fillable([
    'codigo',
    'nombre',
    'especificaciones',
    'precio_mayoreo',
    'precio_menudeo',
    'existencia',
    'publicado',
    'activo',
])]
class Producto extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'especificaciones' => 'array',
            'existencia' => 'integer',
            'publicado' => 'boolean',
            'activo' => 'boolean',
        ];
    }

    /** @return BelongsToMany<Area, $this> */
    public function areas(): BelongsToMany
    {
        return $this->belongsToMany(Area::class, 'producto_area')
            ->withPivot('orden')
            ->orderByPivot('orden');
    }

    /** @return BelongsToMany<Insumo, $this, ProductoInsumo, 'pivot'> */
    public function insumos(): BelongsToMany
    {
        return $this->belongsToMany(Insumo::class, 'producto_insumo')
            ->using(ProductoInsumo::class)
            ->withPivot('cantidad_por_pieza');
    }

    /** @return HasMany<MovimientoProducto, $this> */
    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoProducto::class);
    }
}
