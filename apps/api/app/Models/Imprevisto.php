<?php

namespace App\Models;

use App\Enums\TipoImprevisto;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use MongoDB\Laravel\Eloquent\Model;

/**
 * Documento de la colección `imprevistos` de MongoDB (docs/modelo-datos.md). `lote_id`, `area_id`
 * y `user_id` apuntan a Postgres como números, sin llave foránea.
 *
 * @property int $lote_id
 * @property int $area_id
 * @property TipoImprevisto $tipo
 * @property string $descripcion
 * @property string|null $maquina
 * @property int|null $piezas_afectadas
 * @property int|null $duracion_minutos
 * @property bool $resuelto
 * @property int $user_id
 * @property Carbon|null $ocurrido_en
 * @property Carbon|null $created_at
 */
#[Fillable([
    'lote_id',
    'area_id',
    'tipo',
    'descripcion',
    'maquina',
    'piezas_afectadas',
    'duracion_minutos',
    'resuelto',
    'user_id',
    'ocurrido_en',
])]
class Imprevisto extends Model
{
    /** El documento solo lleva `created_at` (docs/modelo-datos.md). */
    public const UPDATED_AT = null;

    protected $connection = 'mongodb';

    protected $table = 'imprevistos';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'lote_id' => 'integer',
            'area_id' => 'integer',
            'user_id' => 'integer',
            'tipo' => TipoImprevisto::class,
            'piezas_afectadas' => 'integer',
            'duracion_minutos' => 'integer',
            'resuelto' => 'boolean',
            'ocurrido_en' => 'datetime',
        ];
    }
}
