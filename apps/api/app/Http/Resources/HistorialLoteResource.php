<?php

namespace App\Http\Resources;

use App\Models\Lote;
use App\Models\LoteArea;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Lote
 */
class HistorialLoteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $anterior = null;

        return [
            'codigo' => $this->codigo,
            'estado' => $this->estado,
            'paradas' => $this->paradas->map(function (LoteArea $parada) use (&$anterior, $request): array {
                $datos = (new ParadaLoteResource($parada))->conAnterior($anterior)->resolve($request);
                $anterior = $parada;

                return [
                    ...$datos,
                    'entrada_por' => $parada->entradaPor?->name,
                    'salida_por' => $parada->salidaPor?->name,
                ];
            })->values(),
        ];
    }
}
