<?php

namespace App\Bitacora;

use App\Enums\AccionBitacora;
use App\Models\Bitacora as EventoBitacora;
use Carbon\CarbonImmutable;
use Illuminate\Pagination\LengthAwarePaginator;
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

    public function auditoria(array $filtros, int $porPagina = 20): LengthAwarePaginator
    {
        $consulta = EventoBitacora::query();

        if (isset($filtros['accion'])) {
            $consulta->where('accion', $filtros['accion']);
        }

        if (isset($filtros['resultado'])) {
            $consulta->where('resultado', $filtros['resultado']);
        }

        if (isset($filtros['entidad'])) {
            $consulta->where('entidad', $filtros['entidad']);
        }

        if (isset($filtros['usuario'])) {
            $consulta->where('user_id', (int) $filtros['usuario']);
        }

        if (isset($filtros['desde'])) {
            $consulta->where('registrado_en', '>=', CarbonImmutable::parse((string) $filtros['desde'])->startOfDay());
        }

        if (isset($filtros['hasta'])) {
            $consulta->where('registrado_en', '<=', CarbonImmutable::parse((string) $filtros['hasta'])->endOfDay());
        }

        if (($filtros['ocultar_logins'] ?? false) === true) {
            $consulta->where('accion', '!=', AccionBitacora::Login->value);
        }

        return $consulta->orderByDesc('registrado_en')->paginate($porPagina);
    }
}
