<?php

namespace App\Http\Resources;

use App\Enums\EstadoLote;
use App\Enums\Rol;
use App\Models\Lote;
use App\Models\LoteArea;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Lote
 */
class LoteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $veCostos = (bool) $request->user()?->hasAnyRole([Rol::Administrador, Rol::Ventas]);

        return [
            'id' => $this->id,
            'codigo' => $this->codigo,
            'estado' => $this->estado,
            'cantidad_objetivo' => $this->cantidad_objetivo,
            'piezas_terminadas' => $this->piezas_terminadas,
            'producto' => $this->whenLoaded('producto', fn () => [
                'id' => $this->producto->id,
                'codigo' => $this->producto->codigo,
                'nombre' => $this->producto->nombre,
            ]),
            'area_actual' => $this->whenLoaded('areaActual', fn () => $this->areaActual === null ? null : [
                'id' => $this->areaActual->id,
                'codigo' => $this->areaActual->codigo,
                'nombre' => $this->areaActual->nombre,
            ]),
            'costo_total' => $this->when($veCostos, $this->costo_total),
            'costo_por_pieza' => $this->when($veCostos, $this->costo_por_pieza),
            'paradas' => $this->whenLoaded('paradas', function () {
                $anterior = null;

                return $this->paradas->map(function (LoteArea $parada) use (&$anterior) {
                    $recurso = (new ParadaLoteResource($parada))->conAnterior($anterior);
                    $anterior = $parada;

                    return $recurso;
                })->values();
            }),
            'insumos' => InsumoLoteResource::collection($this->whenLoaded('insumos')),
            'confirmado_en' => $this->confirmado_en,
            'cancelado_en' => $this->cancelado_en,
            'motivo_cancelacion' => $this->motivo_cancelacion,
            'admite_devolucion' => $this->whenLoaded('paradas', fn () => in_array($this->estado, [EstadoLote::Creado, EstadoLote::EnProceso], true)
                && $this->paradas->first()?->salida_ocurrido_en === null),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
