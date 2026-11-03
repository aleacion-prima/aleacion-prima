<?php

namespace App\Models;

use App\Enums\TipoMovimientoProducto;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['producto_id', 'tipo', 'cantidad', 'lote_id', 'pedido_id', 'motivo', 'user_id', 'ocurrido_en'])]
class MovimientoProducto extends Model
{
    protected $table = 'movimientos_producto';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipo' => TipoMovimientoProducto::class,
            'cantidad' => 'integer',
            'ocurrido_en' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Producto, $this> */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    /** @return BelongsTo<Lote, $this> */
    public function lote(): BelongsTo
    {
        return $this->belongsTo(Lote::class);
    }
}
