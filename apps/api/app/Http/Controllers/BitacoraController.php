<?php

namespace App\Http\Controllers;

use App\Bitacora\LectorBitacora;
use App\Http\Requests\ConsultarBitacoraRequest;
use App\Http\Resources\BitacoraEventoResource;
use App\Models\User;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;

class BitacoraController extends Controller
{
    /** Bitácora general de auditoría, solo para el administrador del panel. */
    public function index(ConsultarBitacoraRequest $request, LectorBitacora $lector): AnonymousResourceCollection
    {
        $eventos = $lector->auditoria($request->validated());

        return BitacoraEventoResource::collection($this->conRegistradores($eventos));
    }

    /**
     * Los documentos guardan el id de Postgres del registrador: se resuelve en
     * bloque para no consultar la BD por cada evento.
     */
    private function conRegistradores(LengthAwarePaginator $eventos): LengthAwarePaginator
    {
        $ids = $eventos->getCollection()->pluck('user_id')->filter()->unique()->all();
        $usuarios = User::query()->whereIn('id', $ids)->get()->keyBy('id');

        foreach ($eventos as $evento) {
            $evento->setRelation('registrador', $evento->user_id === null ? null : $usuarios->get($evento->user_id));
        }

        return $eventos;
    }
}
