<?php

namespace App\Http\Controllers;

use App\Http\Requests\AjustarLoteRequest;
use App\Http\Resources\AjusteResource;
use App\Http\Resources\LoteResource;
use App\Lotes\AjustarLote;
use App\Models\Lote;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class AjusteLoteController extends Controller
{
    public function index(Lote $lote): AnonymousResourceCollection
    {
        return AjusteResource::collection(
            $lote->ajustes()
                ->with(['usuario', 'loteArea.area', 'loteInsumo.insumo'])
                ->latest('id')
                ->get(),
        );
    }

    public function store(
        AjustarLoteRequest $request,
        Lote $lote,
        AjustarLote $ajustar,
    ): JsonResponse {
        $usuario = $request->user();
        abort_unless($usuario instanceof User, Response::HTTP_UNAUTHORIZED);

        $ajuste = $ajustar->ejecutar($lote, $request->validated(), $usuario);

        return response()->json([
            'ajuste' => (new AjusteResource(
                $ajuste->load(['usuario', 'loteArea.area', 'loteInsumo.insumo'])
            ))->resolve($request),
            'lote' => (new LoteResource($this->loteActualizado($lote)))->resolve($request),
        ], Response::HTTP_CREATED);
    }

    private function loteActualizado(Lote $lote): Lote
    {
        return $lote->refresh()->load(['producto', 'areaActual', 'paradas.area', 'insumos.insumo']);
    }
}
