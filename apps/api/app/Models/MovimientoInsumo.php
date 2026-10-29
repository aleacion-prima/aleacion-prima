<?php

namespace App\Models;

use App\Enums\TipoMovimientoInsumo;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'insumo_id',
    'tipo',
    'cantidad',
    'costo_unitario',
    'lote_proveedor',
    'proveedor',
    'lote_id',
    'motivo',
    'user_id',
    'ocurrido_en',
])]
class MovimientoInsumo extends Model
{
    protected $table = 'movimientos_insumo';

    protected function casts(): array
    {
        return [
            'tipo' => TipoMovimientoInsumo::class,
            'cantidad' => 'decimal:3',
            'costo_unitario' => 'decimal:4',
            'ocurrido_en' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Insumo, $this>
     */
    public function insumo(): BelongsTo
    {
        return $this->belongsTo(Insumo::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
