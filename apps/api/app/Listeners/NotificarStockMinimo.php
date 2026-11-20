<?php

namespace App\Listeners;

use App\Enums\Rol;
use App\Events\StockMinimoAlcanzado;
use App\Models\Insumo;
use App\Models\User;
use App\Notifications\StockMinimoBajo;
use Illuminate\Support\Facades\Notification;

/**
 * Avisa a los administradores activos cuando un insumo llega a su stock mínimo.
 */
class NotificarStockMinimo
{
    public function handle(StockMinimoAlcanzado $evento): void
    {
        $insumo = Insumo::query()->find($evento->insumoId);

        if (! $insumo instanceof Insumo) {
            return;
        }

        $admins = User::query()->role(Rol::Administrador)->where('activo', true)->get();

        Notification::send($admins, new StockMinimoBajo($insumo));
    }
}
