<?php

namespace App\Imprevistos;

use App\Enums\TipoImprevisto;
use App\Events\ImprevistoRegistrado;
use App\Models\Imprevisto;
use App\Models\Lote;
use App\Models\LoteArea;
use App\Models\User;
use Carbon\CarbonImmutable;

/**
 * Registra un imprevisto de una parada. Solo escribe en MongoDB: no toca `lote_areas`.
 */
class RegistradorImprevisto
{
    public function __construct(private readonly AlmacenImprevistos $almacen) {}

    public function registrar(
        Lote $lote,
        LoteArea $parada,
        TipoImprevisto $tipo,
        string $descripcion,
        ?string $maquina,
        ?int $piezasAfectadas,
        ?int $duracionMinutos,
        bool $resuelto,
        ?CarbonImmutable $ocurridoEn,
        User $usuario,
    ): Imprevisto {
        $imprevisto = $this->almacen->guardar([
            'lote_id' => $lote->id,
            'area_id' => $parada->area_id,
            'tipo' => $tipo,
            'descripcion' => $descripcion,
            'maquina' => $maquina,
            'piezas_afectadas' => $piezasAfectadas,
            'duracion_minutos' => $duracionMinutos,
            'resuelto' => $resuelto,
            'user_id' => $usuario->id,
            'ocurrido_en' => $ocurridoEn ?? CarbonImmutable::now(),
        ]);

        ImprevistoRegistrado::dispatch(
            $lote->codigo,
            (string) $imprevisto->getKey(),
            $parada->area_id,
            $tipo->value,
            $resuelto,
        );

        return $imprevisto;
    }
}
