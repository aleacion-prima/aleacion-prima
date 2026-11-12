<?php

namespace App\Models;

use App\Enums\EstadoLote;
use Database\Factories\LoteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property EstadoLote $estado
 */
#[Fillable(['producto_id', 'pedido_id', 'cantidad_objetivo'])]
class Lote extends Model
{
    /** @use HasFactory<LoteFactory> */
    use HasFactory;

    public function getRouteKeyName(): string
    {
        return 'codigo';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'estado' => EstadoLote::class,
            'cantidad_objetivo' => 'integer',
            'piezas_terminadas' => 'integer',
            'costo_material' => 'decimal:4',
            'costo_mano_obra' => 'decimal:4',
            'costo_maquina' => 'decimal:4',
            'costo_total' => 'decimal:4',
            'costo_por_pieza' => 'decimal:4',
            'confirmado_en' => 'immutable_datetime',
            'cancelado_en' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<Producto, $this>
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    /** @return BelongsTo<Area, $this> */
    public function areaActual(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'area_actual_id');
    }

    /** @return HasMany<LoteArea, $this> */
    public function paradas(): HasMany
    {
        return $this->hasMany(LoteArea::class)->orderBy('orden');
    }

    /** @return HasMany<LoteInsumo, $this> */
    public function insumos(): HasMany
    {
        return $this->hasMany(LoteInsumo::class);
    }

    /**
     * Los ajustes no cuelgan del lote por una sola llave: apuntan a una parada
     * o a un insumo asignado, así que se juntan con dos subconsultas.
     *
     * @return Builder<Ajuste>
     */
    public function ajustes(): Builder
    {
        return Ajuste::query()->where(function (Builder $consulta): void {
            $consulta
                ->whereIn('lote_area_id', $this->paradas()->getQuery()->select('lote_areas.id'))
                ->orWhereIn('lote_insumo_id', $this->insumos()->getQuery()->select('lote_insumos.id'));
        });
    }

    /** @return BelongsTo<User, $this> */
    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    /** @return BelongsTo<User, $this> */
    public function confirmador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmado_por');
    }

    /** @return BelongsTo<User, $this> */
    public function cancelador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelado_por');
    }
}
