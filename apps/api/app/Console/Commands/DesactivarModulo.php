<?php

namespace App\Console\Commands;

use App\Support\Modulos;
use Illuminate\Console\Command;
use Laravel\Pennant\Feature;

class DesactivarModulo extends Command
{
    protected $signature = 'modulos:desactivar {modulo}';

    protected $description = 'Apaga un módulo (feature flag) para todos';

    public function handle(): int
    {
        $modulo = (string) $this->argument('modulo');

        if (! Modulos::existe($modulo)) {
            $this->error("Módulo desconocido: {$modulo}.");

            return self::FAILURE;
        }

        Feature::deactivate($modulo);
        $this->info("Módulo desactivado: {$modulo}.");

        return self::SUCCESS;
    }
}
