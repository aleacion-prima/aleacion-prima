<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'lote_area_id',
    'lote_insumo_id',
    'insumo_id',
    'campo',
    'valores',
    'motivo',
    'user_id',
])]
class Ajuste extends Model
{
    protected function casts(): array
    {
        return [
            'valores' => 'array',
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
     * @return BelongsTo<LoteArea, $this>
     */
    public function loteArea(): BelongsTo
    {
        return $this->belongsTo(LoteArea::class);
    }

    /**
     * @return BelongsTo<LoteInsumo, $this>
     */
    public function loteInsumo(): BelongsTo
    {
        return $this->belongsTo(LoteInsumo::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
