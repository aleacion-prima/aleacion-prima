<?php

namespace App\Http\Controllers;

use App\Http\Requests\CancelarLoteRequest;
use App\Http\Resources\LoteResource;
use App\Lotes\CierreLote;
use App\Models\Lote;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CierreLoteController extends Controller
{
    public function confirmar(Request $request, Lote $lote, CierreLote $cierre): LoteResource
    {
        $cierre->confirmar($lote, $this->usuario($request));

        return $this->respuesta($lote);
    }

    public function cancelar(CancelarLoteRequest $request, Lote $lote, CierreLote $cierre): LoteResource
    {
        $cierre->cancelar(
            $lote,
            $request->string('motivo')->toString(),
            $request->devoluciones(),
            $this->usuario($request),
        );

        return $this->respuesta($lote);
    }

    private function usuario(Request $request): User
    {
        $usuario = $request->user();
        abort_unless($usuario instanceof User, Response::HTTP_UNAUTHORIZED);

        return $usuario;
    }

    private function respuesta(Lote $lote): LoteResource
    {
        return new LoteResource($lote->refresh()->load(['producto', 'areaActual', 'paradas.area', 'insumos.insumo']));
    }
}
