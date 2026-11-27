<?php

namespace App\Listeners;

use App\Bitacora\Bitacora;
use App\Enums\AccionBitacora;
use App\Enums\EntidadBitacora;
use App\Enums\ResultadoBitacora;
use App\Events\ModulosActualizados;
use App\Support\Modulos;

// Escucha los eventos de Pennant y difunde el estado actual de los módulos.
class DifundirModulos
{
    public function handle(object $evento): void
    {
        ModulosActualizados::dispatch(Modulos::estado());

        // FeatureUpdated y FeatureUpdatedForAllScopes traen `feature` y `value`;
        // los módulos no tienen id entero, así que el nombre viaja en `datos`.
        $propiedades = get_object_vars($evento);
        $modulo = (string) ($propiedades['feature'] ?? '');
        $activo = (bool) ($propiedades['value'] ?? false);
        $actor = auth()->user()->name ?? 'Sistema';

        Bitacora::auditar(
            AccionBitacora::CambiarModulo,
            ResultadoBitacora::Exito,
            EntidadBitacora::Modulo,
            null,
            $actor.($activo ? ' activó' : ' desactivó')." el módulo {$modulo}",
            ['modulo' => $modulo, 'activo' => $activo],
        );
    }
}
