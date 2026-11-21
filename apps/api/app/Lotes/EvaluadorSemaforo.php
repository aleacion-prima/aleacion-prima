<?php

namespace App\Lotes;

use App\Enums\EstadoLote;
use App\Enums\Semaforo;
use App\Imprevistos\AlmacenImprevistos;
use App\Models\Imprevisto;
use App\Models\Lote;
use App\Models\LoteArea;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Valor calculado por lote, nunca capturado: el peor color de cuatro señales y la lista
 * de motivos que lo explican. Los umbrales están en config/planta.php ('semaforo').
 *
 * Solo evalúa lotes abiertos. Las comparaciones usan enteros y BigDecimal, sin float.
 */
class EvaluadorSemaforo
{
    /** Relación manual donde `precargar()` deja los imprevistos abiertos de cada lote. */
    private const RELACION_IMPREVISTOS = 'imprevistosAbiertos';

    public function __construct(private AlmacenImprevistos $almacen) {}

    /**
     * @param  Collection<int, Lote>  $lotes
     */
    public function precargar(Collection $lotes): void
    {
        $abiertos = $lotes->filter(fn (Lote $lote): bool => $this->esAbierto($lote));

        if ($abiertos->isEmpty()) {
            return;
        }

        try {
            $imprevistos = $this->almacen
                ->abiertosDeLotes($abiertos->map(fn (Lote $lote): int => $lote->id)->values()->all())
                ->groupBy('lote_id');
        } catch (Throwable $e) {
            report($e);
            $imprevistos = null;
        }

        foreach ($abiertos as $lote) {
            if ($imprevistos === null) {
                $lote->setRelation(self::RELACION_IMPREVISTOS, null);

                continue;
            }

            $lote->setRelation(self::RELACION_IMPREVISTOS, ($imprevistos->get($lote->id) ?? collect())->values());
        }
    }

    /**
     * @param  bool  $veCostos  La regla del costo solo corre para quien puede ver costos.
     */
    public function evaluar(Lote $lote, bool $veCostos): ?ResultadoSemaforo
    {
        if (! $this->esAbierto($lote)) {
            return null;
        }

        $paradas = $lote->paradas;
        $merma = (int) $paradas->sum(fn (LoteArea $parada): int => $parada->merma_piezas ?? 0);

        $imprevistos = $this->imprevistosAbiertos($lote);

        $hallazgos = array_values(array_filter([
            $this->merma($lote, $merma),
            $this->imprevistos($imprevistos ?? collect()),
            $veCostos ? $this->costo($lote, $merma) : null,
            $this->capturasTardias($paradas),
        ]));

        usort($hallazgos, fn (array $a, array $b): int => $b['nivel']->gravedad() <=> $a['nivel']->gravedad());

        return new ResultadoSemaforo(
            $hallazgos[0]['nivel'] ?? Semaforo::Verde,
            array_map(fn (array $hallazgo): string => $hallazgo['motivo'], $hallazgos),
            incompleto: $imprevistos === null,
        );
    }

    /**
     * Merma acumulada de todas las paradas contra la cantidad objetivo.
     *
     * @return array{nivel: Semaforo, motivo: string}|null
     */
    private function merma(Lote $lote, int $merma): ?array
    {
        $objetivo = $lote->cantidad_objetivo;

        if ($merma <= 0 || $objetivo <= 0) {
            return null;
        }

        $ambar = (int) config('planta.semaforo.merma_ambar_pct');
        $rojo = (int) config('planta.semaforo.merma_rojo_pct');

        $nivel = match (true) {
            $merma * 100 >= $objetivo * $rojo => Semaforo::Rojo,
            $merma * 100 >= $objetivo * $ambar => Semaforo::Ambar,
            default => null,
        };

        if ($nivel === null) {
            return null;
        }

        $porcentaje = BigDecimal::of((string) ($merma * 100))->dividedBy((string) $objetivo, 1, RoundingMode::HalfUp);

        return $this->hallazgo($nivel, sprintf('Merma acumulada de %d de %d piezas (%s %%).', $merma, $objetivo, $porcentaje));
    }

    /**
     * Imprevistos sin resolver y cuánto lleva abierto el más antiguo.
     *
     * @return array{nivel: Semaforo, motivo: string}|null
     */
    private function imprevistos(Collection $abiertos): ?array
    {
        if ($abiertos->isEmpty()) {
            return null;
        }

        $masAntiguo = (int) $abiertos->map(fn (Imprevisto $imprevisto): int => $this->momento($imprevisto))->min();
        $minutos = intdiv(max(0, now()->getTimestamp() - $masAntiguo), 60);

        $nivel = match (true) {
            $minutos > (int) config('planta.semaforo.imprevisto_rojo_minutos') => Semaforo::Rojo,
            $abiertos->count() >= (int) config('planta.semaforo.imprevistos_ambar_abiertos') => Semaforo::Ambar,
            default => null,
        };

        if ($nivel === null) {
            return null;
        }

        $cuantos = $abiertos->count();

        return $this->hallazgo($nivel, sprintf(
            '%d %s sin resolver; el más antiguo lleva %s abierto.',
            $cuantos,
            $cuantos === 1 ? 'imprevisto' : 'imprevistos',
            $this->duracion($minutos),
        ));
    }

    /**
     * Costo por pieza proyectado contra el precio de mayoreo. Se proyecta con el costo a la
     * fecha entre las piezas que quedan (objetivo menos merma). El motivo nombra el umbral
     * cruzado, nunca el precio ni la relación real.
     *
     * @return array{nivel: Semaforo, motivo: string}|null
     */
    private function costo(Lote $lote, int $merma): ?array
    {
        $piezas = $lote->cantidad_objetivo - $merma;
        $precio = BigDecimal::of((string) $lote->producto->precio_mayoreo);

        if ($piezas <= 0 || ! $precio->isPositive()) {
            return null;
        }

        $ambar = (int) config('planta.semaforo.costo_ambar_pct');
        $rojo = (int) config('planta.semaforo.costo_rojo_pct');

        // costo ÷ piezas ≥ precio × pct ÷ 100  ⇔  costo × 100 ≥ precio × pct × piezas
        $costo = BigDecimal::of((string) $lote->costo_total)->multipliedBy('100');
        $limite = fn (int $porcentaje): BigDecimal => $precio->multipliedBy((string) ($porcentaje * $piezas));

        foreach ([[$rojo, Semaforo::Rojo], [$ambar, Semaforo::Ambar]] as [$porcentaje, $nivel]) {
            if ($costo->isGreaterThanOrEqualTo($limite($porcentaje))) {
                return $this->hallazgo($nivel, sprintf('El costo por pieza proyectado llega al %d %% del precio de mayoreo.', $porcentaje));
            }
        }

        return null;
    }

    /**
     * Solo pone ámbar: las capturas tardías se permiten (ver glosario) y no son un fallo.
     *
     * @param  Collection<int, LoteArea>  $paradas
     * @return array{nivel: Semaforo, motivo: string}|null
     */
    private function capturasTardias(Collection $paradas): ?array
    {
        $tardias = (int) $paradas->sum(fn (LoteArea $parada): int => (int) $parada->entradaTardia() + (int) $parada->salidaTardia());

        if ($tardias < (int) config('planta.semaforo.capturas_tardias_ambar')) {
            return null;
        }

        return $this->hallazgo(Semaforo::Ambar, sprintf(
            '%d capturas tardías: se registraron con más de %d min de retraso.',
            $tardias,
            (int) config('planta.umbral_captura_tardia'),
        ));
    }

    /** @return array{nivel: Semaforo, motivo: string} */
    private function hallazgo(Semaforo $nivel, string $motivo): array
    {
        return ['nivel' => $nivel, 'motivo' => $motivo];
    }

    /**
     * Los imprevistos sin resolver del lote, o `null` si no se pudieron leer.
     *
     * @return Collection<int, Imprevisto>|null
     */
    private function imprevistosAbiertos(Lote $lote): ?Collection
    {
        if ($lote->relationLoaded(self::RELACION_IMPREVISTOS)) {
            /** @var Collection<int, Imprevisto>|null $precargados */
            $precargados = $lote->getRelation(self::RELACION_IMPREVISTOS);

            return $precargados;
        }

        try {
            return $this->almacen->delLote($lote->id)
                ->filter(fn (Imprevisto $imprevisto): bool => ! $imprevisto->resuelto)
                ->values();
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }

    private function esAbierto(Lote $lote): bool
    {
        return in_array($lote->estado, [EstadoLote::Creado, EstadoLote::EnProceso, EstadoLote::PorConfirmar], true);
    }

    private function momento(Imprevisto $imprevisto): int
    {
        return $imprevisto->ocurrido_en?->getTimestamp()
            ?? $imprevisto->created_at?->getTimestamp()
            ?? now()->getTimestamp();
    }

    private function duracion(int $minutos): string
    {
        $horas = intdiv($minutos, 60);
        $resto = $minutos % 60;

        return match (true) {
            $horas === 0 => "{$resto} min",
            $resto === 0 => "{$horas} h",
            default => "{$horas} h {$resto} min",
        };
    }
}
