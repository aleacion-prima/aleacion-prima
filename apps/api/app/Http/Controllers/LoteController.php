<?php

namespace App\Http\Controllers;

use App\Enums\EstadoLote;
use App\Http\Requests\CrearLoteRequest;
use App\Http\Resources\LoteResource;
use App\Lotes\CreadorLote;
use App\Models\Lote;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class LoteController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return LoteResource::collection(
            Lote::query()
                ->with(['producto', 'areaActual', 'paradas.area'])
                ->when($request->boolean('activos'), fn ($consulta) => $consulta->where(
                    fn ($activos) => $activos
                        ->whereIn('estado', [EstadoLote::Creado, EstadoLote::EnProceso])
                        ->orWhere('updated_at', '>=', now((string) config('planta.zona_horaria'))->startOfDay()),
                ))
                ->latest('id')
                ->get(),
        );
    }

    public function show(Lote $lote): LoteResource
    {
        return new LoteResource($lote->load(['producto', 'areaActual', 'paradas.area', 'insumos.insumo']));
    }

    public function store(CrearLoteRequest $request, CreadorLote $creador): JsonResponse
    {
        $usuario = $request->user();
        abort_unless($usuario instanceof User, Response::HTTP_UNAUTHORIZED);

        $recorrido = $request->has('recorrido')
            ? array_map('intval', array_values((array) $request->validated('recorrido')))
            : null;

        $lote = $creador->crear(
            Producto::query()->findOrFail($request->integer('producto_id')),
            $request->integer('cantidad_objetivo'),
            $recorrido,
            $usuario,
        );

        return (new LoteResource($lote))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }
}
