<?php

namespace App\Console\Commands;

use App\Lotes\CalculadorCostoLote;
use App\Models\Lote;
use Illuminate\Console\Command;

class RecalcularCostoLote extends Command
{
    protected $signature = 'lotes:recalcular';

    protected $description = 'Recalcula los costos de todos los lotes (red de seguridad contra la caché desincronizada).';

    public function handle(CalculadorCostoLote $calculador): int
    {
        $total = 0;

        Lote::query()->chunkById(100, function ($lotes) use ($calculador, &$total): void {
            foreach ($lotes as $lote) {
                $calculador->recalcular($lote);
                $total++;
            }
        });

        $this->info("Costos recalculados en {$total} lotes.");

        return self::SUCCESS;
    }
}
