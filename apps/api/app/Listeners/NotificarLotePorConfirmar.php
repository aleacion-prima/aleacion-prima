<?php

namespace App\Listeners;

use App\Enums\EstadoLote;
use App\Enums\Rol;
use App\Events\LoteActualizado;
use App\Models\User;
use App\Notifications\LotePorConfirmar;
use Illuminate\Support\Facades\Notification;

/**
 * Avisa a los administradores activos cuando un lote entra al estado
 * por_confirmar. LoteActualizado se emite en cada cambio del lote, así que solo
 * notifica en esa transición, no en los demás.
 */
class NotificarLotePorConfirmar
{
    public function handle(LoteActualizado $evento): void
    {
        if ($evento->lote->estado !== EstadoLote::PorConfirmar) {
            return;
        }

        $admins = User::query()->role(Rol::Administrador)->where('activo', true)->get();

        Notification::send($admins, new LotePorConfirmar($evento->lote));
    }
}
