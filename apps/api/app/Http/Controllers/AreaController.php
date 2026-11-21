<?php

namespace App\Http\Controllers;

use App\Http\Requests\AreaRequest;
use App\Http\Resources\AreaResource;
use App\Models\Area;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
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
                ->with('encargados:id,name')
                ->when($request->has('activo'), fn ($consulta) => $consulta->where('activo', $request->boolean('activo')))
                ->orderBy('orden_catalogo')
                ->get()
        );
    }

    /**
     * Devuelve los catálogos y listas auxiliares necesarias para formularios (ej. encargados activos).
     */
    public function opciones(): JsonResponse
    {
        return response()->json([
            'encargados' => User::role('encargado')
                ->where('activo', true)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (User $usuario) => ['id' => $usuario->id, 'name' => $usuario->name])
                ->values(),
        ]);
    }

    public function show(Area $area): AreaResource
    {
        return new AreaResource($area->load('encargados:id,name'));
    }

    public function store(AreaRequest $request): JsonResponse
    {
        $datos = $request->safe()->except('encargados');
        $datos['activo'] = $request->boolean('activo', true);

        $area = DB::transaction(function () use ($datos, $request): Area {
            $area = Area::create($datos);
            $area->encargados()->sync($request->input('encargados', []));

            return $area;
        });

        return (new AreaResource($area->refresh()->load('encargados:id,name')))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Cambiar tarifas no toca los lotes existentes: cada `lote_areas` guarda su copia (ADR-0009).
     */
    public function update(AreaRequest $request, Area $area): AreaResource
    {
        DB::transaction(function () use ($request, $area): void {
            $area->update($request->safe()->except('encargados'));

            if ($request->has('encargados')) {
                $area->encargados()->sync($request->input('encargados'));
            }
        });

        return new AreaResource($area->refresh()->load('encargados:id,name'));
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
