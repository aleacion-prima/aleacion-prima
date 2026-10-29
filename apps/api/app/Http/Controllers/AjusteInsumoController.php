<?php

namespace App\Http\Controllers;

use App\Http\Requests\AjustarExistenciaRequest;
use App\Http\Resources\AjusteResource;
use App\Http\Resources\MovimientoInsumoResource;
use App\Inventario\AjustarExistencia;
use App\Models\Insumo;
use Illuminate\Http\JsonResponse;

class AjusteInsumoController extends Controller
{
    public function store(
        AjustarExistenciaRequest $request,
        Insumo $insumo,
        AjustarExistencia $ajustar,
    ): JsonResponse {
        $resultado = $ajustar->ejecutar(
            $insumo,
            $request->validated(),
            $request->user()->id,
        );

        $respuesta = (new AjusteResource($resultado['ajuste']->load('insumo', 'usuario')))
            ->resolve($request);
        $respuesta['movimiento'] = (new MovimientoInsumoResource($resultado['movimiento']))
            ->resolve($request);

        return response()->json($respuesta, 201);
    }
}
