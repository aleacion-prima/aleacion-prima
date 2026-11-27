<?php

namespace App\Listeners;

use App\Bitacora\Bitacora;
use App\Enums\AccionBitacora;
use App\Enums\EntidadBitacora;
use App\Enums\ResultadoBitacora;
use App\Models\User;
use Illuminate\Auth\Events\Logout;
use Illuminate\Contracts\Auth\Authenticatable;

class AuditarCierreSesion
{
    public function handle(Logout $evento): void
    {
        // El evento Logout de Laravel tipa `user` como no-nulo, pero en runtime
        // llega null en el pre-logout del flujo 2FA (SesionController@iniciar
        // llama a Auth::guard('web')->logout() sin sesión activa). Se documenta
        // el contrato real para que la guarda de abajo no sea "siempre falsa".
        /** @var Authenticatable|null $usuario */
        $usuario = $evento->user;

        // Un logout sin usuario no tiene nada que registrar.
        if ($usuario === null) {
            return;
        }

        $id = (int) $usuario->getAuthIdentifier();
        $nombre = $usuario instanceof User ? $usuario->name : (string) $usuario->getAuthIdentifier();

        Bitacora::auditar(
            AccionBitacora::Logout,
            ResultadoBitacora::Exito,
            EntidadBitacora::Usuario,
            $id,
            "{$nombre} cerró sesión",
            [],
            $id,
        );
    }
}
