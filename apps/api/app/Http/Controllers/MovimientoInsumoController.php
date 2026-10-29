<?php

namespace App\Http\Controllers;

use App\Http\Resources\MovimientoInsumoResource;
use App\Models\Insumo;
use Illuminate\Http\JsonResponse;

class MovimientoInsumoController extends Controller
{
    public function index(Insumo $insumo): JsonResponse
    {
        return response()->json(MovimientoInsumoResource::collection(
            $insumo->movimientos()->latest('ocurrido_en')->get(),
        ));
    }
}
