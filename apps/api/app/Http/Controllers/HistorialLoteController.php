<?php

namespace App\Http\Controllers;

use App\Http\Resources\HistorialLoteResource;
use App\Models\Lote;

class HistorialLoteController extends Controller
{
    /**
     * Paradas del lote en orden, con quién capturó cada entrada y salida. Lo comparten el
     * panel y la app móvil, así que no incluye costos para quien no los ve.
     */
    public function __invoke(Lote $lote): HistorialLoteResource
    {
        return new HistorialLoteResource(
            $lote->load(['paradas.area', 'paradas.entradaPor:id,name', 'paradas.salidaPor:id,name']),
        );
    }
}
