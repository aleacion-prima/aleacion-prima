<?php

namespace App\Http\Controllers;

use App\Http\Requests\AreaRequest;
use App\Http\Resources\AreaResource;
use App\Models\Area;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class AreaController extends Controller
{
    /**
     * Catálogo en el orden natural de la planta. `?activo=1` devuelve solo las áreas
     * disponibles para recorridos nuevos; `?activo=0`, solo las desactivadas.
     */
    public function index(Request $request)
    {
        return AreaResource::collection(
            Area::query()
                ->when($request->has('activo'), fn ($consulta) => $consulta->where('activo', $request->boolean('activo')))
                ->orderBy('orden_catalogo')
                ->get()
        );
    }

    public function show(Area $area): AreaResource
    {
        return new AreaResource($area);
    }

    public function store(AreaRequest $request): JsonResponse
    {
        $area = Area::create([...$request->validated(), 'activo' => $request->boolean('activo', true)]);

        return (new AreaResource($area->refresh()))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Cambiar tarifas no toca los lotes existentes: cada `lote_areas` guarda su copia (ADR-0009).
     */
    public function update(AreaRequest $request, Area $area): AreaResource
    {
        $area->update($request->validated());

        return new AreaResource($area->refresh());
    }

    /**
     * Desactivar, no borrar: los lotes que usaron el área la siguen referenciando.
     * Se reactiva editándola con `activo = true`.
     */
    public function destroy(Area $area): Response
    {
        if ($area->obligatoria) {
            throw ValidationException::withMessages([
                'activo' => 'Un área obligatoria no puede desactivarse: todo recorrido la incluye.',
            ]);
        }

        $area->update(['activo' => false]);

        return response()->noContent();
    }
}
