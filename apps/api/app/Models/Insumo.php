<?php

namespace App\Models;

use Database\Factories\InsumoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'codigo',
    'nombre',
    'categoria',
    'unidad',
    'costo_unitario',
    'existencia',
    'stock_minimo',
    'activo',
])]
class Insumo extends Model
{
    /** @use HasFactory<InsumoFactory> */
    use HasFactory;

    public const CATEGORIAS = ['metal', 'moldeo', 'empaque'];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'existencia' => 0,
        'activo' => true,
    ];

    protected function casts(): array
    {
        return [
            'costo_unitario' => 'decimal:4',
            'existencia' => 'decimal:3',
            'stock_minimo' => 'decimal:3',
            'activo' => 'boolean',
        ];
    }

    /**
     * @return HasMany<MovimientoInsumo, $this>
     */
    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoInsumo::class);
    }
}
