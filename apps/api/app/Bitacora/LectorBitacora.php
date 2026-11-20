<?php

namespace App\Bitacora;

use App\Models\Bitacora as EventoBitacora;
use Illuminate\Support\Collection;

/**
 * Lectura de la bitácora del lote, separada de la escritura (EscritorBitacora).
 * En producción la implementa LectorBitacoraMongo; en pruebas, LectorBitacoraFake,
 * porque los tests no tocan MongoDB.
 */
interface LectorBitacora
{
    /**
     * Eventos del lote en orden cronológico (por `ocurrido_en`).
     *
     * @return Collection<int, EventoBitacora>
     */
    public function delLote(int $loteId): Collection;
}
