<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
        return $this->belongsToMany(Area::class, 'producto_area')   // tu nombre de tabla
            ->withPivot('orden')
            ->orderByPivot('orden');
    }

    /** @return BelongsToMany<Insumo, $this> */
    public function insumos(): BelongsToMany
    {
        return $this->belongsToMany(Insumo::class, 'producto_insumo')
            ->withPivot('cantidad');
    }
}
