<?php

namespace App\Http\Controllers;

use App\Http\Resources\InsumoResource;
use App\Http\Resources\ProductoInventarioResource;
use App\Models\Insumo;
use App\Models\Producto;
use Illuminate\Http\JsonResponse;

class InventarioController extends Controller
{
    public function insumos(): JsonResponse
    {
        return response()->json(InsumoResource::collection(
            Insumo::query()->where('activo', true)->orderBy('nombre')->get(),
        ));
    }

    public function productos(): JsonResponse
    {
        return response()->json(ProductoInventarioResource::collection(
            Producto::query()->where('activo', true)->orderBy('nombre')->get(),
        ));
    }
}
