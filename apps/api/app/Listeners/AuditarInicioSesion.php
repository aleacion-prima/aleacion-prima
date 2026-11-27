<?php

namespace App\Listeners;

use App\Bitacora\Bitacora;
use App\Enums\AccionBitacora;
use App\Enums\EntidadBitacora;
use App\Enums\ResultadoBitacora;
use App\Models\User;
use Illuminate\Auth\Events\Login;

class AuditarInicioSesion
{
    public function handle(Login $evento): void
    {
        $usuario = $evento->user;
        $id = (int) $usuario->getAuthIdentifier();
        $nombre = $usuario instanceof User ? $usuario->name : (string) $usuario->getAuthIdentifier();

        Bitacora::auditar(
            AccionBitacora::Login,
            ResultadoBitacora::Exito,
            EntidadBitacora::Usuario,
            $id,
            "{$nombre} inició sesión",
            [],
            $id,
        );
    }
}
