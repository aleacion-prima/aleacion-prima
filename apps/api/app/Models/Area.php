<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable([
    'codigo',
    'nombre',
    'descripcion',
    'tarifa_mano_obra_hora',
    'costo_maquina_hora',
    'obligatoria',
    'orden_catalogo',
    'activo',
])]
class Area extends Model
{
    /**
     * @return BelongsToMany<User, $this>
     */
    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
}
