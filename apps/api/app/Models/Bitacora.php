<?php

namespace App\Models;

use Illuminate\Support\Carbon;
use MongoDB\Laravel\Eloquent\Model;

/**
 * Bitácora de eventos del lote (colección `bitacora` en MongoDB).
 *
 * Es un registro append-only: nunca se edita ni se borra. El único punto de
 * escritura es App\Bitacora\Bitacora::registrar(); por eso este modelo no
 * expone nada más que la lectura y la inserción.
 *
 * @property int|null $lote_id
 * @property int|null $area_id
 * @property string $tipo
 * @property string $descripcion
 * @property array<string, mixed>|null $datos
 * @property int|null $user_id
 * @property Carbon $ocurrido_en
 * @property Carbon $registrado_en
 * @property bool $captura_tardia
 * @property Area|null $area
 * @property User|null $registrador
 */
class Bitacora extends Model
{
    protected $connection = 'mongodb';

    protected $table = 'bitacora';

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
