<?php

namespace App\Models;

use Database\Factories\LoteAreaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['lote_id', 'area_id', 'orden', 'tarifa_mano_obra_hora', 'costo_maquina_hora'])]
class LoteArea extends Model
{
    /** @use HasFactory<LoteAreaFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'orden' => 'integer',
            'tarifa_mano_obra_hora' => 'decimal:4',
            'costo_maquina_hora' => 'decimal:4',
            'piezas_recibidas' => 'integer',
            'piezas_entregadas' => 'integer',
            'merma_piezas' => 'integer',
            'merma_material' => 'decimal:3',
            'retorno_kg' => 'decimal:3',
            'entrada_ocurrido_en' => 'immutable_datetime',
            'entrada_registrado_en' => 'immutable_datetime',
            'salida_ocurrido_en' => 'immutable_datetime',
            'salida_registrado_en' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Lote, $this> */
    public function lote(): BelongsTo
    {
        return $this->belongsTo(Lote::class);
    }

    /** @return BelongsTo<Area, $this> */
    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }
}
