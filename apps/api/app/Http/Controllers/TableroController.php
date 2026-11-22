<?php

namespace App\Http\Controllers;

use App\Enums\EstadoLote;
use App\Http\Resources\LoteResource;
use App\Lotes\EvaluadorSemaforo;
use App\Models\Lote;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TableroController extends Controller
{
    public function __invoke(EvaluadorSemaforo $semaforo): AnonymousResourceCollection
    {
        $lotes = Lote::query()
            ->with(['producto', 'areaActual', 'paradas.area'])
            ->whereIn('estado', [EstadoLote::Creado, EstadoLote::EnProceso, EstadoLote::PorConfirmar])
            ->latest('id')
            ->get();

        $semaforo->precargar($lotes);

        return LoteResource::collection($lotes);
    }
}
