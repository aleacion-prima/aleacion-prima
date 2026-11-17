<?php

namespace App\Console\Commands;

use App\Enums\EstadoLote;
use App\Lotes\CalculadorCostoLote;
use App\Models\Lote;
use Illuminate\Console\Command;

class RecalcularCostoLote extends Command
{
    protected $signature = 'lotes:recalcular {--cerrados : Incluye también los lotes terminados y cancelados (su costo ya está congelado)}';

    protected $description = 'Recalcula los costos de los lotes abiertos (red de seguridad contra la caché desincronizada).';

    /** @var list<string> */
    private const CAMPOS = ['costo_material', 'costo_mano_obra', 'costo_maquina', 'costo_total', 'costo_por_pieza'];

    public function handle(CalculadorCostoLote $calculador): int
    {
        $incluirCerrados = (bool) $this->option('cerrados');
        $cerrados = [EstadoLote::Terminado->value, EstadoLote::Cancelado->value];

        $recalculados = 0;
        $cambiados = 0;

        Lote::query()
            ->when(! $incluirCerrados, fn ($consulta) => $consulta->whereNotIn('estado', $cerrados))
            ->chunkById(100, function ($lotes) use ($calculador, &$recalculados, &$cambiados): void {
                foreach ($lotes as $lote) {
                    $antes = $lote->only(self::CAMPOS);
                    $calculador->recalcular($lote);
                    $despues = $lote->only(self::CAMPOS);
                    $recalculados++;

                    if ($antes === $despues) {
                        continue;
                    }

                    $cambiados++;
                    $this->line(sprintf(
                        '%s (%s): costo_total %s → %s · costo_por_pieza %s → %s',
                        $lote->codigo,
                        $lote->estado->value,
                        $antes['costo_total'],
                        $despues['costo_total'],
                        $antes['costo_por_pieza'] ?? '—',
                        $despues['costo_por_pieza'] ?? '—',
                    ));
                }
            });

        $this->info("Costos recalculados en {$recalculados} lotes; {$cambiados} cambiaron.");

        if (! $incluirCerrados) {
            $saltados = Lote::query()->whereIn('estado', $cerrados)->count();

            if ($saltados > 0) {
                $this->warn("{$saltados} lotes terminados o cancelados no se tocaron (usa --cerrados para incluirlos).");
            }
        }

        return self::SUCCESS;
    }
}
