<?php

namespace App\Listeners;

use App\Bitacora\Bitacora;
use App\Enums\AccionBitacora;
use App\Enums\EntidadBitacora;
use App\Enums\ResultadoBitacora;
use Illuminate\Auth\Events\Failed;

class AuditarIntentoFallido
{
    public function handle(Failed $evento): void
    {
        $correo = $evento->credentials['email'] ?? null;
        $correo = is_string($correo) ? $correo : '';
        $usuario = $evento->user;
        $entidadId = $usuario === null ? null : (int) $usuario->getAuthIdentifier();

        Bitacora::auditar(
            AccionBitacora::Login,
            ResultadoBitacora::Error,
            EntidadBitacora::Usuario,
            $entidadId,
            "Intento de inicio de sesión fallido para {$correo}",
            ['email' => $correo],
        );
    }
}
