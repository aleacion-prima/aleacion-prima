<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

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
    protected function casts(): array
    {
        return [
            'especificaciones' => 'array',
            'existencia' => 'integer',
            'publicado' => 'boolean',
            'activo' => 'boolean',
        ];
    }
}
