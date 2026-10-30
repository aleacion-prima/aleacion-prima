<?php

namespace App\Http\Controllers;

use App\Http\Requests\InsumoRequest;
use App\Http\Resources\InsumoResource;
use App\Models\Insumo;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

class InsumoController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return InsumoResource::collection(Insumo::query()->orderBy('codigo')->get());
    }

    public function store(InsumoRequest $request): InsumoResource
    {
        return new InsumoResource(Insumo::create($request->validated()));
    }

    public function show(Insumo $insumo): InsumoResource
    {
        return new InsumoResource($insumo);
    }

    public function update(InsumoRequest $request, Insumo $insumo): InsumoResource
    {
        $insumo->update($request->validated());

        return new InsumoResource($insumo);
    }

    public function destroy(Insumo $insumo): InsumoResource
    {
        if ($insumo->codigo === config('planta.insumo_retorno')) {
            throw ValidationException::withMessages([
                'activo' => 'El insumo de retorno no se puede desactivar.',
            ]);
        }

        $insumo->update(['activo' => false]);

        return new InsumoResource($insumo);
    }
}
