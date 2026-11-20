<?php

namespace App\Http\Controllers;

use App\Bitacora\LectorBitacora;
use App\Http\Resources\BitacoraEventoResource;
use App\Models\Area;
use App\Models\Bitacora as EventoBitacora;
use App\Models\Lote;
use App\Models\User;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Collection;

class BitacoraLoteController extends Controller
{
    /** Eventos del lote, solo para Ventas y Administrador. */
    public function __invoke(Lote $lote, LectorBitacora $lector): AnonymousResourceCollection
    {
        return BitacoraEventoResource::collection($this->conNombres($lector->delLote($lote->id)));
    }

    /**
     * Los documentos de Mongo guardan ids de Postgres: se resuelven aquí, en bloque.
     *
     * @param  Collection<int, EventoBitacora>  $eventos
     * @return Collection<int, EventoBitacora>
     */
    private function conNombres(Collection $eventos): Collection
    {
        $areas = Area::query()->whereIn('id', $eventos->pluck('area_id')->filter()->unique()->all())->get()->keyBy('id');
        $usuarios = User::query()->whereIn('id', $eventos->pluck('user_id')->filter()->unique()->all())->get()->keyBy('id');

        foreach ($eventos as $evento) {
            $evento->setRelation('area', $evento->area_id === null ? null : $areas->get($evento->area_id));
            $evento->setRelation('registrador', $evento->user_id === null ? null : $usuarios->get($evento->user_id));
        }

        return $eventos;
    }
}
