<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegistrarEntradaRequest;
use App\Http\Resources\MovimientoInsumoResource;
use App\Inventario\RegistroEntrada;
use App\Models\Insumo;
use Illuminate\Http\JsonResponse;

class EntradaInsumoController extends Controller
{
    public function store(
        RegistrarEntradaRequest $request,
        Insumo $insumo,
        RegistroEntrada $registro,
    ): JsonResponse {
        $movimiento = $registro->ejecutar(
            $insumo,
            $request->validated(),
            $request->user()->id,
        );

        return (new MovimientoInsumoResource($movimiento->load('insumo', 'usuario')))
            ->response()
            ->setStatusCode(201);
    }
}
