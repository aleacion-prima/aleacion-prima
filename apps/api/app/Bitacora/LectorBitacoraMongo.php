<?php

namespace App\Bitacora;

use App\Models\Bitacora as EventoBitacora;
use Illuminate\Support\Collection;

class LectorBitacoraMongo implements LectorBitacora
{
    public function delLote(int $loteId): Collection
    {
        return EventoBitacora::query()
            ->where('lote_id', $loteId)
            ->orderBy('ocurrido_en')
            ->orderBy('created_at')
            ->get();
    }
}
