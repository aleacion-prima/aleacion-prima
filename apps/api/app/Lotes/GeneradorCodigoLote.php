<?php

namespace App\Lotes;

use App\Models\Lote;

class GeneradorCodigoLote
{
    public function siguiente(): string
    {
        $prefijo = 'LOT-'.now()->setTimezone((string) config('planta.zona_horaria'))->format('Ymd').'-';

        $ultimo = Lote::query()
            ->where('codigo', 'like', $prefijo.'%')
            ->orderByDesc('codigo')
            ->lockForUpdate()
            ->value('codigo');

        $consecutivo = $ultimo === null ? 1 : (int) substr((string) $ultimo, -2) + 1;

        return $prefijo.str_pad((string) $consecutivo, 2, '0', STR_PAD_LEFT);
    }
}
