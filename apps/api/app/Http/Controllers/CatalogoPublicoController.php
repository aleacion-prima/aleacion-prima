<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductoPublicoResource;
use App\Models\Producto;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CatalogoPublicoController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return ProductoPublicoResource::collection($this->publicados()->orderBy('nombre')->get());
    }

    public function show(int $producto): ProductoPublicoResource
    {
        return new ProductoPublicoResource($this->publicados()->findOrFail($producto));
    }

    /**
     * @return Builder<Producto>
     */
    private function publicados(): Builder
    {
        return Producto::query()->where('publicado', true)->where('activo', true);
    }
}
