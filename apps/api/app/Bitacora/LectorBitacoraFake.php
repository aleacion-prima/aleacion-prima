<?php

namespace App\Bitacora;

use App\Enums\AccionBitacora;
use App\Models\Bitacora as EventoBitacora;
use Carbon\CarbonImmutable;
use Illuminate\Pagination\LengthAwarePaginator;
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

    public function auditoria(array $filtros, int $porPagina = 20): LengthAwarePaginator
    {
        $eventos = $this->eventos
            ->filter(fn (EventoBitacora $evento) => $this->cumple($evento, $filtros))
            ->sortByDesc(fn (EventoBitacora $evento) => $evento->registrado_en->getTimestamp())
            ->values();

        $pagina = max(1, (int) request()->query('page', 1));

        return new LengthAwarePaginator(
            $eventos->forPage($pagina, $porPagina)->values(),
            $eventos->count(),
            $porPagina,
            $pagina,
        );
    }

    /**
     * @param  array<string, mixed>  $filtros
     */
    private function cumple(EventoBitacora $evento, array $filtros): bool
    {
        if (isset($filtros['accion']) && $evento->accion !== $filtros['accion']) {
            return false;
        }

        if (isset($filtros['resultado']) && $evento->resultado !== $filtros['resultado']) {
            return false;
        }

        if (isset($filtros['entidad']) && $evento->entidad !== $filtros['entidad']) {
            return false;
        }

        if (isset($filtros['usuario']) && $evento->user_id !== (int) $filtros['usuario']) {
            return false;
        }

        if (isset($filtros['desde']) && $evento->registrado_en->lt(CarbonImmutable::parse((string) $filtros['desde'])->startOfDay())) {
            return false;
        }

        if (isset($filtros['hasta']) && $evento->registrado_en->gt(CarbonImmutable::parse((string) $filtros['hasta'])->endOfDay())) {
            return false;
        }

        if (($filtros['ocultar_logins'] ?? false) === true && $evento->accion === AccionBitacora::Login->value) {
            return false;
        }

        return true;
    }
}
