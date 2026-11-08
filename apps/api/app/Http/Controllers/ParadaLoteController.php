<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegistrarEntradaParadaRequest;
use App\Http\Requests\RegistrarSalidaParadaRequest;
use App\Http\Resources\LoteResource;
use App\Lotes\CapturaParada;
use App\Models\Lote;
use App\Models\LoteArea;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;

class ParadaLoteController extends Controller
{
    public function entrada(RegistrarEntradaParadaRequest $request, Lote $lote, LoteArea $parada, CapturaParada $captura): LoteResource
    {
        $captura->entrada($lote, $parada, $request->integer('piezas_recibidas'), $request->ocurridoEn(), $this->usuario($request->user()));

        return $this->respuesta($lote);
    }

    public function salida(RegistrarSalidaParadaRequest $request, Lote $lote, LoteArea $parada, CapturaParada $captura): LoteResource
    {
        $captura->salida(
            $lote,
            $parada,
            $request->integer('piezas_entregadas'),
            $request->filled('merma_material') ? (string) $request->input('merma_material') : null,
            $request->filled('retorno_kg') ? (string) $request->input('retorno_kg') : null,
            $request->ocurridoEn(),
            $this->usuario($request->user()),
        );

        return $this->respuesta($lote);
    }

    private function usuario(mixed $usuario): User
    {
        abort_unless($usuario instanceof User, Response::HTTP_UNAUTHORIZED);

        return $usuario;
    }

    private function respuesta(Lote $lote): LoteResource
    {
        return new LoteResource($lote->refresh()->load(['producto', 'areaActual', 'paradas.area', 'insumos.insumo']));
    }
}
