<?php

namespace App\Recorridos;

use App\Models\Area;
use Illuminate\Support\Collection;

class ValidadorRecorrido
{
    /**
     * @param  list<int>  $ids
     */
    public function error(array $ids, bool $permitirRepetir): ?string
    {
        if (! $permitirRepetir && count($ids) !== count(array_unique($ids))) {
            return 'No repitas un área en el recorrido.';
        }

        $areas = Area::query()->whereIn('id', $ids)->get()->keyBy('id');
        $enRecorrido = $this->areasEnOrden($ids, $areas);

        $inactivas = $enRecorrido->reject(fn (Area $area) => $area->activo)->unique('id');
        if ($inactivas->isNotEmpty()) {
            return 'Las áreas '.$inactivas->pluck('codigo')->implode(', ').' están inactivas y no pueden entrar en un recorrido nuevo.';
        }

        $obligatorias = Area::query()
            ->activas()
            ->where('obligatoria', true)
            ->orderBy('orden_catalogo')
            ->get();

        $faltantes = $obligatorias->pluck('codigo')->diff($enRecorrido->pluck('codigo'));
        if ($faltantes->isNotEmpty()) {
            return $faltantes->count() === 1
                ? 'Falta el área obligatoria '.$faltantes->first().'.'
                : 'Faltan las áreas obligatorias '.$faltantes->implode(', ').'.';
        }

        $ordenObligatorias = $enRecorrido
            ->filter(fn (Area $area) => $area->obligatoria)
            ->unique('id')
            ->pluck('id')
            ->values()
            ->all();

        if ($ordenObligatorias !== $obligatorias->pluck('id')->all()) {
            return 'Las áreas obligatorias deben estar en este orden: '.$obligatorias->pluck('codigo')->implode(' → ').'.';
        }

        return null;
    }

    /**
     * @param  list<int>  $ids
     * @param  Collection<int, Area>  $areas
     * @return Collection<int, Area>
     */
    private function areasEnOrden(array $ids, Collection $areas): Collection
    {
        return collect($ids)
            ->map(fn (int $id) => $areas->get($id))
            ->filter()
            ->values();
    }
}
