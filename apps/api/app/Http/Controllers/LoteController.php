<?php

namespace App\Http\Controllers;

use App\Enums\EstadoLote;
use App\Enums\Rol;
use App\Http\Requests\CrearLoteRequest;
use App\Http\Resources\LoteResource;
use App\Lotes\CreadorLote;
use App\Lotes\EvaluadorSemaforo;
use App\Models\Lote;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class LoteController extends Controller
{
    public function index(Request $request, EvaluadorSemaforo $semaforo): AnonymousResourceCollection
    {
        $lotes = Lote::query()
            ->with(['producto', 'areaActual', 'paradas.area'])
            ->when($this->soloSusAreas($request), fn ($consulta, User $usuario) => $consulta->whereHas(
                'paradas',
                fn ($paradas) => $paradas->whereIn('area_id', $usuario->areas()->select('areas.id')),
            ))
            ->when($request->boolean('activos'), fn ($consulta) => $consulta->where(
                fn ($activos) => $activos
                    ->whereIn('estado', [EstadoLote::Creado, EstadoLote::EnProceso])
                    ->orWhere('updated_at', '>=', now((string) config('planta.zona_horaria'))->startOfDay()),
            ))
            ->latest('id')
            ->get();

        // Una sola consulta a Mongo por los imprevistos abiertos de todos los lotes de la lista.
        if ($request->user()?->veSemaforo()) {
            $semaforo->precargar($lotes);
        }

        return LoteResource::collection($lotes);
    }

    private function soloSusAreas(Request $request): ?User
    {
        $usuario = $request->user();

        return $usuario instanceof User && ! $usuario->hasAnyRole([Rol::Administrador, Rol::Ventas])
            ? $usuario
            : null;
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
