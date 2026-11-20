<?php

namespace App\Imprevistos;

use App\Models\Imprevisto;
use Illuminate\Support\Collection;
use MongoDB\BSON\ObjectId;

/**
 * Almacén de pruebas: guarda los documentos en un arreglo, sin conexión a Mongo. Se enlaza en
 * `tests/Pest.php`, así que ningún test de Feature llega a Mongo real.
 */
final class AlmacenImprevistosFake implements AlmacenImprevistos
{
    /** @var array<string, Imprevisto> */
    private array $documentos = [];

    public function guardar(array $datos): Imprevisto
    {
        $imprevisto = new Imprevisto($datos);
        $imprevisto->_id = new ObjectId;
        $imprevisto->created_at = now();

        $this->documentos[(string) $imprevisto->getKey()] = $imprevisto;

        return $imprevisto;
    }

    public function delLote(int $loteId): Collection
    {
        return collect($this->documentos)
            ->filter(fn (Imprevisto $imprevisto) => $imprevisto->lote_id === $loteId)
            ->sortByDesc(fn (Imprevisto $imprevisto) => $imprevisto->ocurrido_en?->getTimestamp() ?? 0)
            ->values();
    }

    public function buscar(int $loteId, string $id): ?Imprevisto
    {
        $imprevisto = $this->documentos[$id] ?? null;

        return $imprevisto?->lote_id === $loteId ? $imprevisto : null;
    }

    public function marcarResuelto(Imprevisto $imprevisto, bool $resuelto): Imprevisto
    {
        $imprevisto->resuelto = $resuelto;

        return $imprevisto;
    }
}
