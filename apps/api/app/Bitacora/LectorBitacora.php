<?php

namespace App\Bitacora;

use App\Models\Bitacora as EventoBitacora;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Lectura de la bitácora, separada de la escritura (EscritorBitacora). En
 * producción la implementa LectorBitacoraMongo; en pruebas, LectorBitacoraFake,
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

    /**
     * Auditoría general, de más nueva a más vieja, paginada. Filtros admitidos:
     * `accion`, `resultado`, `entidad`, `usuario`, `desde`, `hasta` y
     * `ocultar_logins`.
     *
     * @param  array<string, mixed>  $filtros
     */
    public function auditoria(array $filtros, int $porPagina = 20): LengthAwarePaginator;
}
