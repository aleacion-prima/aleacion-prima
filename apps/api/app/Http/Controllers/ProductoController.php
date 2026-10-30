<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductoRequest;
use App\Http\Resources\ProductoResource;
use App\Models\Area;
use App\Models\Insumo;
use App\Models\Producto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class ProductoController extends Controller
{
    public function index(Request $request)
    {
        return ProductoResource::collection(
            Producto::query()
                ->with(['areas', 'insumos'])
                ->when($request->has('activo'), fn ($consulta) => $consulta->where('activo', $request->boolean('activo')))
                ->orderBy('codigo')
                ->get()
        );
    }

    public function opciones(): JsonResponse
    {
        return response()->json([
            'areas' => Area::query()->activas()->orderBy('orden_catalogo')
                ->get(['id', 'codigo', 'nombre', 'obligatoria', 'orden_catalogo']),
            'insumos' => Insumo::query()->where('activo', true)->orderBy('codigo')
                ->get(['id', 'codigo', 'nombre', 'categoria', 'unidad']),
        ]);
    }

    public function show(Producto $producto): ProductoResource
    {
        return new ProductoResource($producto->load(['areas', 'insumos']));
    }

    public function store(ProductoRequest $request): JsonResponse
    {
        $datos = $request->validated();

        $producto = DB::transaction(function () use ($datos, $request) {
            $producto = Producto::create([
                ...$this->atributos($datos),
                'activo' => $request->boolean('activo', true),
            ]);
            $this->guardarRecorridoYReceta($producto, $datos);

            return $producto;
        });

        return (new ProductoResource($producto->load(['areas', 'insumos'])))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(ProductoRequest $request, Producto $producto): ProductoResource
    {
        $datos = $request->validated();

        DB::transaction(function () use ($producto, $datos) {
            $producto->update([...$this->atributos($datos), 'activo' => $datos['activo']]);
            $this->guardarRecorridoYReceta($producto, $datos);
        });

        return new ProductoResource($producto->refresh()->load(['areas', 'insumos']));
    }

    public function destroy(Producto $producto): Response
    {
        $producto->update(['activo' => false]);

        return response()->noContent();
    }

    /**
     * @param  array<string, mixed>  $datos
     * @return array<string, mixed>
     */
    private function atributos(array $datos): array
    {
        return [
            'codigo' => $datos['codigo'],
            'nombre' => $datos['nombre'],
            'especificaciones' => $datos['especificaciones'] ?? [],
            'precio_mayoreo' => $datos['precio_mayoreo'],
            'precio_menudeo' => $datos['precio_menudeo'],
        ];
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    private function guardarRecorridoYReceta(Producto $producto, array $datos): void
    {
        $producto->areas()->detach();
        foreach (array_values($datos['recorrido']) as $indice => $areaId) {
            $producto->areas()->attach($areaId, ['orden' => $indice + 1]);
        }

        $producto->insumos()->sync(
            collect($datos['receta'])->mapWithKeys(fn (array $renglon) => [
                (int) $renglon['insumo_id'] => ['cantidad_por_pieza' => $renglon['cantidad_por_pieza']],
            ])->all()
        );
    }
}
