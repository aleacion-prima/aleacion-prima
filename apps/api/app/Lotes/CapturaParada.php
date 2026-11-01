<?php

namespace App\Lotes;

use App\Enums\CategoriaInsumo;
use App\Enums\EstadoLote;
use App\Enums\TipoMovimientoInsumo;
use App\Events\LoteActualizado;
use App\Models\Insumo;
use App\Models\Lote;
use App\Models\LoteArea;
use App\Models\User;
use Brick\Math\BigDecimal;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CapturaParada
{
    public function entrada(Lote $lote, LoteArea $parada, int $piezas, ?CarbonImmutable $ocurridoEn, User $usuario): LoteArea
    {
        return DB::transaction(function () use ($lote, $parada, $piezas, $ocurridoEn, $usuario): LoteArea {
            $lote = $this->bloquear($lote);
            $parada = $this->bloquearParada($parada);
            $ocurrido = $ocurridoEn ?? CarbonImmutable::now();

            if (! in_array($lote->estado, [EstadoLote::Creado, EstadoLote::EnProceso], true)) {
                $this->fallar('parada', 'El lote ya no admite capturas.');
            }

            if ($parada->entrada_ocurrido_en !== null) {
                $this->fallar('parada', 'Esta parada ya tiene entrada.');
            }

            $anterior = $this->anterior($lote, $parada);

            if ($anterior !== null && $anterior->salida_ocurrido_en === null) {
                $this->fallar('parada', 'Primero registra la salida de '.$anterior->area?->nombre.'.');
            }

            if ($anterior?->salida_ocurrido_en !== null && $ocurrido->lessThan($anterior->salida_ocurrido_en)) {
                $this->fallar('ocurrido_en', 'La entrada no puede ser antes de la salida de la parada anterior.');
            }

            $parada->forceFill([
                'piezas_recibidas' => $piezas,
                'entrada_ocurrido_en' => $ocurrido,
                'entrada_registrado_en' => CarbonImmutable::now(),
                'entrada_user_id' => $usuario->id,
            ])->save();

            $lote->forceFill([
                'estado' => EstadoLote::EnProceso,
                'area_actual_id' => $parada->area_id,
            ])->save();

            LoteActualizado::dispatch($lote);

            return $parada;
        });
    }

    public function salida(
        Lote $lote,
        LoteArea $parada,
        int $piezas,
        ?string $mermaMaterial,
        ?string $retornoKg,
        ?CarbonImmutable $ocurridoEn,
        User $usuario,
    ): LoteArea {
        return DB::transaction(function () use ($lote, $parada, $piezas, $mermaMaterial, $retornoKg, $ocurridoEn, $usuario): LoteArea {
            $lote = $this->bloquear($lote);
            $parada = $this->bloquearParada($parada);
            $ocurrido = $ocurridoEn ?? CarbonImmutable::now();

            if ($lote->estado !== EstadoLote::EnProceso) {
                $this->fallar('parada', 'El lote no está en proceso.');
            }

            if ($parada->entrada_ocurrido_en === null || $parada->piezas_recibidas === null) {
                $this->fallar('parada', 'Primero registra la entrada a esta parada.');
            }

            if ($parada->salida_ocurrido_en !== null) {
                $this->fallar('parada', 'Esta parada ya tiene salida.');
            }

            if ($piezas > $parada->piezas_recibidas) {
                $this->fallar('piezas_entregadas', "No puedes entregar más piezas de las que recibiste ({$parada->piezas_recibidas}).");
            }

            if ($ocurrido->lessThan($parada->entrada_ocurrido_en)) {
                $this->fallar('ocurrido_en', 'La salida no puede ser antes de la entrada.');
            }

            $retorno = $retornoKg === null ? BigDecimal::zero() : BigDecimal::of($retornoKg);

            if ($retorno->isPositive()) {
                $this->validarRetorno($lote, $parada, $retorno);
            }

            $parada->forceFill([
                'piezas_entregadas' => $piezas,
                'merma_piezas' => $parada->piezas_recibidas - $piezas,
                'merma_material' => $mermaMaterial,
                'retorno_kg' => $retorno->isPositive() ? (string) $retorno : null,
                'salida_ocurrido_en' => $ocurrido,
                'salida_registrado_en' => CarbonImmutable::now(),
                'salida_user_id' => $usuario->id,
            ])->save();

            if ($retorno->isPositive()) {
                $this->registrarRetorno($lote, $retorno, $ocurrido, $usuario);
            }

            $esUltima = ! $lote->paradas()->where('orden', '>', $parada->orden)->exists();

            $lote->forceFill([
                'area_actual_id' => null,
                'estado' => $esUltima ? EstadoLote::PorConfirmar : EstadoLote::EnProceso,
            ])->save();

            LoteActualizado::dispatch($lote);

            return $parada;
        });
    }

    private function bloquear(Lote $lote): Lote
    {
        return Lote::query()->lockForUpdate()->findOrFail($lote->id);
    }

    private function bloquearParada(LoteArea $parada): LoteArea
    {
        return LoteArea::query()->with('area')->lockForUpdate()->findOrFail($parada->id);
    }

    private function anterior(Lote $lote, LoteArea $parada): ?LoteArea
    {
        return $lote->paradas()
            ->with('area')
            ->where('orden', '<', $parada->orden)
            ->reorder('orden', 'desc')
            ->first();
    }

    private function validarRetorno(Lote $lote, LoteArea $parada, BigDecimal $retorno): void
    {
        if ($parada->area?->codigo !== config('planta.area_retorno')) {
            $this->fallar('retorno_kg', 'El retorno de metal solo se registra en la salida de Desmolde.');
        }

        $metalAsignado = $lote->insumos()
            ->whereHas('insumo', fn ($consulta) => $consulta->where('categoria', CategoriaInsumo::Metal->value))
            ->pluck('cantidad')
            ->reduce(fn (BigDecimal $total, $cantidad) => $total->plus((string) $cantidad), BigDecimal::zero());

        if ($retorno->isGreaterThan($metalAsignado)) {
            $this->fallar('retorno_kg', "El retorno no puede pasar del metal asignado al lote ({$metalAsignado} kg).");
        }
    }

    private function registrarRetorno(Lote $lote, BigDecimal $retorno, CarbonImmutable $ocurrido, User $usuario): void
    {
        $insumo = Insumo::query()
            ->where('codigo', config('planta.insumo_retorno'))
            ->lockForUpdate()
            ->first();

        if ($insumo === null) {
            $this->fallar('retorno_kg', 'No existe el insumo de retorno configurado.');
        }

        $insumo->movimientos()->create([
            'tipo' => TipoMovimientoInsumo::Retorno,
            'cantidad' => (string) $retorno,
            'costo_unitario' => $insumo->costo_unitario,
            'lote_id' => $lote->id,
            'user_id' => $usuario->id,
            'ocurrido_en' => $ocurrido,
        ]);

        $insumo->update([
            'existencia' => (string) BigDecimal::of((string) $insumo->existencia)->plus($retorno),
        ]);
    }

    private function fallar(string $campo, string $mensaje): never
    {
        throw ValidationException::withMessages([$campo => $mensaje]);
    }
}
