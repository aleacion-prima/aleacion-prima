<?php

namespace App\Lotes;

use App\Enums\CategoriaInsumo;
use App\Enums\EstadoLote;
use App\Enums\TipoMovimientoInsumo;
use App\Events\LoteActualizado;
use App\Inventario\DetectorStockMinimo;
use App\Models\Area;
use App\Models\Insumo;
use App\Models\Lote;
use App\Models\Producto;
use App\Models\User;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreadorLote
{
    public function __construct(
        private GeneradorCodigoLote $generador,
        private DetectorStockMinimo $detector,
    ) {}

    /**
     * @param  list<int>|null  $recorrido
     */
    public function crear(Producto $producto, int $cantidad, ?array $recorrido, User $usuario): Lote
    {
        return DB::transaction(function () use ($producto, $cantidad, $recorrido, $usuario): Lote {
            $idsRecorrido = $recorrido ?? $producto->areas()->pluck('areas.id')->map(fn ($id) => (int) $id)->all();
            $necesidades = $this->necesidades($producto, $cantidad);

            $insumos = Insumo::query()
                ->whereIn('id', array_keys($necesidades))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $this->validarExistencia($insumos, $necesidades);
            $this->validarColada($insumos, $necesidades);

            $lote = new Lote(['producto_id' => $producto->id, 'cantidad_objetivo' => $cantidad]);
            $lote->forceFill([
                'codigo' => $this->generador->siguiente(),
                'estado' => EstadoLote::Creado,
                'creado_por' => $usuario->id,
            ])->save();

            $this->crearParadas($lote, $idsRecorrido);
            $this->asignarInsumos($lote, $insumos, $necesidades, $usuario);

            LoteActualizado::dispatch($lote);

            return $lote->load(['producto', 'areaActual', 'paradas.area', 'insumos.insumo']);
        });
    }

    /**
     * @return array<int, BigDecimal>
     */
    private function necesidades(Producto $producto, int $cantidad): array
    {
        $necesidades = [];

        foreach ($producto->insumos()->get() as $insumo) {
            $porPieza = (string) $insumo->getRelationValue('pivot')?->getAttribute('cantidad_por_pieza');
            $necesidades[$insumo->id] = BigDecimal::of($porPieza)
                ->multipliedBy($cantidad)
                ->toScale(3, RoundingMode::HalfUp);
        }

        return $necesidades;
    }

    /**
     * @param  Collection<int, Insumo>  $insumos
     * @param  array<int, BigDecimal>  $necesidades
     */
    private function validarExistencia(Collection $insumos, array $necesidades): void
    {
        foreach ($necesidades as $insumoId => $necesario) {
            $insumo = $insumos->get($insumoId);

            if ($insumo === null) {
                throw ValidationException::withMessages([
                    'cantidad_objetivo' => 'Un insumo de la receta ya no existe.',
                ]);
            }

            if (BigDecimal::of((string) $insumo->existencia)->isLessThan($necesario)) {
                throw ValidationException::withMessages([
                    'cantidad_objetivo' => sprintf(
                        'No hay suficiente %s: se necesitan %s %s y hay %s.',
                        $insumo->nombre,
                        $necesario,
                        $insumo->unidad,
                        $insumo->existencia,
                    ),
                ]);
            }
        }
    }

    /**
     * @param  Collection<int, Insumo>  $insumos
     * @param  array<int, BigDecimal>  $necesidades
     */
    private function validarColada(Collection $insumos, array $necesidades): void
    {
        $metal = BigDecimal::zero();

        foreach ($necesidades as $insumoId => $necesario) {
            if ($insumos->get($insumoId)?->categoria === CategoriaInsumo::Metal->value) {
                $metal = $metal->plus($necesario);
            }
        }

        $capacidad = BigDecimal::of((string) config('planta.capacidad_colada_kg'));

        if ($metal->isGreaterThan($capacidad)) {
            throw ValidationException::withMessages([
                'cantidad_objetivo' => sprintf(
                    'El metal del lote (%s kg) no cabe en una colada de %s kg.',
                    $metal,
                    $capacidad->strippedOfTrailingZeros(),
                ),
            ]);
        }
    }

    /**
     * @param  list<int>  $idsRecorrido
     */
    private function crearParadas(Lote $lote, array $idsRecorrido): void
    {
        $areas = Area::query()->whereIn('id', $idsRecorrido)->get()->keyBy('id');

        foreach ($idsRecorrido as $indice => $areaId) {
            $area = $areas->get($areaId);

            if ($area === null) {
                continue;
            }

            $lote->paradas()->create([
                'area_id' => $area->id,
                'orden' => $indice + 1,
                'tarifa_mano_obra_hora' => $area->tarifa_mano_obra_hora,
                'costo_maquina_hora' => $area->costo_maquina_hora,
            ]);
        }
    }

    /**
     * @param  Collection<int, Insumo>  $insumos
     * @param  array<int, BigDecimal>  $necesidades
     */
    private function asignarInsumos(Lote $lote, Collection $insumos, array $necesidades, User $usuario): void
    {
        foreach ($necesidades as $insumoId => $necesario) {
            $insumo = $insumos->get($insumoId);

            if ($insumo === null) {
                continue;
            }

            $anterior = BigDecimal::of((string) $insumo->existencia);
            $nueva = $anterior->minus($necesario);

            $lote->insumos()->create([
                'insumo_id' => $insumo->id,
                'cantidad' => (string) $necesario,
                'costo_unitario' => $insumo->costo_unitario,
            ]);

            $insumo->movimientos()->create([
                'tipo' => TipoMovimientoInsumo::Asignacion,
                'cantidad' => (string) $necesario->negated(),
                'costo_unitario' => $insumo->costo_unitario,
                'lote_id' => $lote->id,
                'user_id' => $usuario->id,
                'ocurrido_en' => now(),
            ]);

            $insumo->update(['existencia' => (string) $nueva]);

            $this->detector->revisar(
                $insumo->id,
                $anterior->toFloat(),
                $nueva->toFloat(),
                (float) $insumo->stock_minimo,
            );
        }
    }
}
