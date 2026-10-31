<?php

namespace App\Models;

use Database\Factories\LoteInsumoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['lote_id', 'insumo_id', 'cantidad', 'costo_unitario', 'lote_proveedor'])]
class LoteInsumo extends Model
{
    /** @use HasFactory<LoteInsumoFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cantidad' => 'decimal:3',
            'costo_unitario' => 'decimal:4',
            'cantidad_devuelta' => 'decimal:3',
        ];
    }

    /** @return BelongsTo<Lote, $this> */
    public function lote(): BelongsTo
    {
        return $this->belongsTo(Lote::class);
    }

    /** @return BelongsTo<Insumo, $this> */
    public function insumo(): BelongsTo
    {
        return $this->belongsTo(Insumo::class);
    }
}
