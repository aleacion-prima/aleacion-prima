<?php

namespace App\Lotes;

use App\Enums\EstadoLote;
use App\Enums\TipoMovimientoInsumo;
use App\Enums\TipoMovimientoProducto;
use App\Events\DisponibilidadActualizada;
use App\Events\LoteActualizado;
use App\Models\Insumo;
use App\Models\Lote;
use App\Models\LoteInsumo;
use App\Models\Producto;
use App\Models\User;
use Brick\Math\BigDecimal;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CierreLote
{
    public function confirmar(Lote $lote, User $usuario): Lote
    {
        return DB::transaction(function () use ($lote, $usuario): Lote {
            $lote = Lote::query()->lockForUpdate()->findOrFail($lote->id);

            if ($lote->estado !== EstadoLote::PorConfirmar) {
                $this->fallar('lote', 'Solo se confirma un lote que terminó su recorrido.');
            }

            $piezas = (int) $lote->paradas()->reorder('orden', 'desc')->value('piezas_entregadas');
            $ahora = CarbonImmutable::now();

            $producto = Producto::query()->lockForUpdate()->findOrFail($lote->producto_id);
            $producto->movimientos()->create([
                'tipo' => TipoMovimientoProducto::Produccion,
                'cantidad' => $piezas,
                'lote_id' => $lote->id,
                'user_id' => $usuario->id,
                'ocurrido_en' => $ahora,
            ]);
            $producto->update(['existencia' => $producto->existencia + $piezas]);

            $lote->forceFill([
                'estado' => EstadoLote::Terminado,
                'piezas_terminadas' => $piezas,
                'confirmado_por' => $usuario->id,
                'confirmado_en' => $ahora,
            ])->save();

            LoteActualizado::dispatch($lote);
            DisponibilidadActualizada::dispatch($producto);

            return $lote;
        });
    }

    /**
     * @param  array<int, array{insumo_id: int, cantidad: string}>  $devoluciones
     */
    public function cancelar(Lote $lote, string $motivo, array $devoluciones, User $usuario): Lote
    {
        return DB::transaction(function () use ($lote, $motivo, $devoluciones, $usuario): Lote {
            $lote = Lote::query()->lockForUpdate()->findOrFail($lote->id);

            if (! in_array($lote->estado, [EstadoLote::Creado, EstadoLote::EnProceso], true)) {
                $this->fallar('lote', 'Solo se cancela un lote creado o en proceso.');
            }

            $ahora = CarbonImmutable::now();

            if ($devoluciones !== []) {
                if (! $this->admiteDevolucion($lote)) {
                    $this->fallar('devoluciones', 'El lote ya salió de su primera parada: la materia prima cuenta como consumida.');
                }

                $this->devolver($lote, $devoluciones, $ahora, $usuario);
            }

            $abierta = $lote->paradas()
                ->whereNotNull('entrada_ocurrido_en')
                ->whereNull('salida_ocurrido_en')
                ->first();

            $abierta?->forceFill([
                'piezas_entregadas' => 0,
                'merma_piezas' => $abierta->piezas_recibidas,
                'salida_ocurrido_en' => $ahora,
                'salida_registrado_en' => $ahora,
                'salida_user_id' => $usuario->id,
            ])->save();

            $lote->forceFill([
                'estado' => EstadoLote::Cancelado,
                'area_actual_id' => null,
                'cancelado_por' => $usuario->id,
                'cancelado_en' => $ahora,
                'motivo_cancelacion' => $motivo,
            ])->save();

            LoteActualizado::dispatch($lote);

            return $lote;
        });
    }

    public function admiteDevolucion(Lote $lote): bool
    {
        return in_array($lote->estado, [EstadoLote::Creado, EstadoLote::EnProceso], true)
            && $lote->paradas()->reorder('orden')->value('salida_ocurrido_en') === null;
    }

    /**
     * @param  array<int, array{insumo_id: int, cantidad: string}>  $devoluciones
     */
    private function devolver(Lote $lote, array $devoluciones, CarbonImmutable $ahora, User $usuario): void
    {
        $asignados = $lote->insumos()->lockForUpdate()->get()->keyBy('insumo_id');

        foreach ($devoluciones as $indice => $devolucion) {
            $asignado = $asignados->get($devolucion['insumo_id']);

            if (! $asignado instanceof LoteInsumo) {
                $this->fallar("devoluciones.{$indice}.insumo_id", 'Ese insumo no está asignado al lote.');
            }

            $cantidad = BigDecimal::of($devolucion['cantidad']);
            $disponible = BigDecimal::of((string) $asignado->cantidad)->minus((string) $asignado->cantidad_devuelta);

            if ($cantidad->isGreaterThan($disponible)) {
                $this->fallar("devoluciones.{$indice}.cantidad", "Solo se pueden devolver {$disponible} de este insumo.");
            }

            $insumo = Insumo::query()->lockForUpdate()->findOrFail($asignado->insumo_id);

            $insumo->movimientos()->create([
                'tipo' => TipoMovimientoInsumo::Devolucion,
                'cantidad' => (string) $cantidad,
                'costo_unitario' => $asignado->costo_unitario,
                'lote_proveedor' => $asignado->lote_proveedor,
                'lote_id' => $lote->id,
                'user_id' => $usuario->id,
                'ocurrido_en' => $ahora,
            ]);

            $insumo->update([
                'existencia' => (string) BigDecimal::of((string) $insumo->existencia)->plus($cantidad),
            ]);

            $asignado->forceFill([
                'cantidad_devuelta' => (string) BigDecimal::of((string) $asignado->cantidad_devuelta)->plus($cantidad),
            ])->save();
        }
    }

    private function fallar(string $campo, string $mensaje): never
    {
        throw ValidationException::withMessages([$campo => $mensaje]);
    }
}
