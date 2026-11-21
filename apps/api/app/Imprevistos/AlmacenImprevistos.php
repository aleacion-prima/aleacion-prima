<?php

namespace App\Imprevistos;

use App\Models\Imprevisto;
use Illuminate\Support\Collection;

interface AlmacenImprevistos
{
    /**
     * @param  array<string, mixed>  $datos
     */
    public function guardar(array $datos): Imprevisto;

    /**
     * Los del lote, el más reciente primero.
     *
     * @return Collection<int, Imprevisto>
     */
    public function delLote(int $loteId): Collection;

    /**
     * Los imprevistos sin resolver de varios lotes, en una sola consulta.
     *
     * @param  list<int>  $loteIds
     * @return Collection<int, Imprevisto>
     */
    public function abiertosDeLotes(array $loteIds): Collection;

    /** Un imprevisto de ese lote; `null` si no existe o es de otro lote. */
    public function buscar(int $loteId, string $id): ?Imprevisto;

    public function marcarResuelto(Imprevisto $imprevisto, bool $resuelto): Imprevisto;
}
