<?php

namespace App\Http\Controllers;

use App\Http\Resources\CostoLoteResource;
use App\Models\Lote;

class CostoLoteController extends Controller
{
    /**
     * Desglose del costo, solo para Ventas y Administrador.
     * Lee los montos que cada operación del lote deja al día: no recalcula.
     */
    public function __invoke(Lote $lote): CostoLoteResource
    {
        return new CostoLoteResource($lote);
    }
}
