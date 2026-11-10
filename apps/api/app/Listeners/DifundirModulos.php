<?php

namespace App\Listeners;

use App\Events\ModulosActualizados;
use App\Support\Modulos;

// Escucha los eventos de Pennant y difunde el estado actual de los módulos.
class DifundirModulos
{
    public function handle(object $evento): void
    {
        ModulosActualizados::dispatch(Modulos::estado());
    }
}
