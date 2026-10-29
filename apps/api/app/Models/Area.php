<?php

namespace App\Models;

use Database\Factories\AreaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['codigo', 'nombre', 'descripcion', 'tarifa_mano_obra_hora', 'costo_maquina_hora', 'obligatoria', 'orden_catalogo', 'activo'])]
class Area extends Model
{
    /** @use HasFactory<AreaFactory> */
    use HasFactory;

    /**
     * Las tarifas son `decimal(14,4)`: se leen como cadena para no perder precisión (RNF-07).
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tarifa_mano_obra_hora' => 'decimal:4',
            'costo_maquina_hora' => 'decimal:4',
            'obligatoria' => 'boolean',
            'orden_catalogo' => 'integer',
            'activo' => 'boolean',
        ];
    }

    /**
     * Áreas que pueden entrar a recorridos nuevos.
     *
     * @param  Builder<Area>  $query
     * @return Builder<Area>
     */
    public function scopeActivas(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    /** @return BelongsToMany<User, $this> */
    public function encargados(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'area_user');
    }
}
