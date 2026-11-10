<?php

namespace App\Console\Commands;

use App\Support\Modulos;
use Illuminate\Console\Command;
use Laravel\Pennant\Feature;

class ActivarModulo extends Command
{
    protected $signature = 'modulos:activar {modulo}';

    protected $description = 'Prende un módulo (feature flag) para todos';

    public function handle(): int
    {
        $modulo = (string) $this->argument('modulo');

        if (! Modulos::existe($modulo)) {
            $this->error("Módulo desconocido: {$modulo}.");

            return self::FAILURE;
        }

        Feature::activate($modulo);
        $this->info("Módulo activado: {$modulo}.");

        return self::SUCCESS;
    }
}
