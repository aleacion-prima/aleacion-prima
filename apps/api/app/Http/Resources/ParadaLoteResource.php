<?php

namespace App\Http\Resources;

use App\Enums\Rol;
use App\Models\LoteArea;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin LoteArea
 */
class ParadaLoteResource extends JsonResource
{
    private ?LoteArea $anterior = null;

    public function conAnterior(?LoteArea $anterior): static
    {
        $this->anterior = $anterior;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $usuario = $request->user();
        $veCostos = $usuario instanceof User && $usuario->hasAnyRole([Rol::Administrador, Rol::Ventas]);

        return [
            'id' => $this->id,
            'orden' => $this->orden,
            'area' => $this->whenLoaded('area', fn () => [
                'id' => $this->area->id,
                'codigo' => $this->area->codigo,
                'nombre' => $this->area->nombre,
            ]),
            'tarifa_mano_obra_hora' => $this->when($veCostos, $this->tarifa_mano_obra_hora),
            'costo_maquina_hora' => $this->when($veCostos, $this->costo_maquina_hora),
            'piezas_recibidas' => $this->piezas_recibidas,
            'piezas_entregadas' => $this->piezas_entregadas,
            'merma_piezas' => $this->merma_piezas,
            'merma_material' => $this->merma_material,
            'retorno_kg' => $this->retorno_kg,
            'discrepancia' => $this->discrepancia(),
            'entrada_ocurrido_en' => $this->entrada_ocurrido_en,
            'entrada_registrado_en' => $this->entrada_registrado_en,
            'entrada_tardia' => $this->esTardia($this->entrada_ocurrido_en, $this->entrada_registrado_en),
            'salida_ocurrido_en' => $this->salida_ocurrido_en,
            'salida_registrado_en' => $this->salida_registrado_en,
            'salida_tardia' => $this->esTardia($this->salida_ocurrido_en, $this->salida_registrado_en),
            'admite_retorno' => $this->whenLoaded('area', fn () => $this->area->codigo === config('planta.area_retorno')),
            'puede_capturar' => $this->puedeCapturar($usuario),
        ];
    }

    private function discrepancia(): ?int
    {
        if ($this->anterior?->piezas_entregadas === null || $this->piezas_recibidas === null) {
            return null;
        }

        $diferencia = $this->piezas_recibidas - $this->anterior->piezas_entregadas;

        return $diferencia === 0 ? null : $diferencia;
    }

    private function esTardia(?CarbonImmutable $ocurrido, ?CarbonImmutable $registrado): bool
    {
        if ($ocurrido === null || $registrado === null) {
            return false;
        }

        return $ocurrido->diffInMinutes($registrado) > (int) config('planta.umbral_captura_tardia');
    }

    private function puedeCapturar(mixed $usuario): bool
    {
        if (! $usuario instanceof User) {
            return false;
        }

        return $usuario->hasRole(Rol::Administrador)
            || $usuario->areas->contains('id', $this->area_id);
    }
}
