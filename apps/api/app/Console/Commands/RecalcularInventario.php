<?php

namespace App\Console\Commands;

use App\Models\Insumo;
use Illuminate\Console\Command;

class RecalcularInventario extends Command
{
    protected $signature = 'inventario:recalcular';

    protected $description = 'Reconstruye la existencia de insumos desde sus movimientos';

    public function handle(): int
    {
        $actualizados = 0;

        Insumo::query()->each(function (Insumo $insumo) use (&$actualizados): void {
            $existencia = (float) $insumo->movimientos()->sum('cantidad');
            $insumo->update(['existencia' => $existencia]);
            $actualizados++;
        });

        $this->info("Existencias recalculadas: {$actualizados}.");

        return self::SUCCESS;
    }
}
