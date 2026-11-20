<?php

namespace App\Bitacora;

use App\Models\Bitacora as EventoBitacora;
use Illuminate\Support\Collection;

/**
 * Lector en memoria para pruebas: devuelve los documentos que se le entregan,
 * sin tocar MongoDB. Se instala con LectorBitacoraFake::con([...]).
 */
class LectorBitacoraFake implements LectorBitacora
{
    /** @var Collection<int, EventoBitacora> */
    private Collection $eventos;

    /**
     * @param  list<array<string, mixed>>  $documentos
     */
    public function __construct(array $documentos = [])
    {
        $this->eventos = collect($documentos)
            ->values()
            ->map(fn (array $documento, int $indice) => (new EventoBitacora($documento))
                ->forceFill(['_id' => "evento-{$indice}"]));
    }

    /**
     * @param  list<array<string, mixed>>  $documentos
     */
    public static function con(array $documentos): self
    {
        $fake = new self($documentos);
        app()->instance(LectorBitacora::class, $fake);

        return $fake;
    }

    public function delLote(int $loteId): Collection
    {
        return $this->eventos
            ->filter(fn (EventoBitacora $evento) => $evento->lote_id === $loteId)
            ->sortBy(fn (EventoBitacora $evento) => $evento->ocurrido_en->getTimestamp())
            ->values();
    }
}
