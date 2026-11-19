<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

/**
 * Bitácora de eventos del lote (colección `bitacora` en MongoDB).
 *
 * Es un registro append-only: nunca se edita ni se borra. El único punto de
 * escritura es App\Bitacora\Bitacora::registrar(); por eso este modelo no
 * expone nada más que la lectura y la inserción.
 */
class Bitacora extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'bitacora';

    // El documento solo tiene created_at (docs/modelo-datos.md); sin esto
    // Eloquent intentaría mantener un updated_at que no existe.
    public const UPDATED_AT = null;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'lote_id',
        'area_id',
        'tipo',
        'descripcion',
        'datos',
        'user_id',
        'ocurrido_en',
        'registrado_en',
        'captura_tardia',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ocurrido_en' => 'datetime',
            'registrado_en' => 'datetime',
            'captura_tardia' => 'boolean',
        ];
    }
}
