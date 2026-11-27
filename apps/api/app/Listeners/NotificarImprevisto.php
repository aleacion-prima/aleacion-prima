<?php

namespace App\Listeners;

use App\Enums\Rol;
use App\Events\ImprevistoRegistrado;
use App\Models\User;
use App\Notifications\ImprevistoRegistrado as NotificacionImprevisto;
use Illuminate\Support\Facades\Notification;

/**
 * Avisa a los administradores activos cuando se registra un imprevisto nuevo. El
 * evento también lleva imprevistos dados de alta como ya resueltos, que no se
 * notifican (no son algo que atender).
 */
class NotificarImprevisto
{
    public function handle(ImprevistoRegistrado $evento): void
    {
        if ($evento->resuelto) {
            return;
        }

        $admins = User::query()->role(Rol::Administrador)->where('activo', true)->get();

        $mensaje = "Imprevisto ({$evento->tipo}) en el lote {$evento->codigoLote}";

        Notification::send($admins, new NotificacionImprevisto($evento->codigoLote, $mensaje));
    }
}
