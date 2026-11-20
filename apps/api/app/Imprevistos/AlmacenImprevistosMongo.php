<?php

namespace App\Imprevistos;

use App\Models\Imprevisto;
use Illuminate\Support\Collection;

class AlmacenImprevistosMongo implements AlmacenImprevistos
{
    public function guardar(array $datos): Imprevisto
    {
        return Imprevisto::query()->create($datos);
    }

    public function delLote(int $loteId): Collection
    {
        return Imprevisto::query()
            ->where('lote_id', $loteId)
            ->orderBy('ocurrido_en', 'desc')
            ->get();
    }

    public function buscar(int $loteId, string $id): ?Imprevisto
    {
        return Imprevisto::query()
            ->where('lote_id', $loteId)
            ->where('_id', $id)
            ->first();
    }

    public function marcarResuelto(Imprevisto $imprevisto, bool $resuelto): Imprevisto
    {
        $imprevisto->update(['resuelto' => $resuelto]);

        return $imprevisto;
    }
}
