<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Código de un solo uso para el segundo factor al iniciar sesión.
 *
 * @property int $user_id
 * @property string $codigo
 * @property Carbon $expira_en
 * @property int $intentos
 */
class CodigoAcceso extends Model
{
    protected $table = 'codigos_acceso';

    /** @var list<string> */
    protected $fillable = ['user_id', 'codigo', 'expira_en', 'intentos'];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vencido(): bool
    {
        return $this->expira_en->isPast();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expira_en' => 'datetime',
            'intentos' => 'integer',
        ];
    }
}
