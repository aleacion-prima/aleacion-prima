<?php

namespace App\Notifications;

use App\Models\Insumo;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

/**
 * Aviso al administrador de que un insumo llegó a su stock mínimo. La dispara el
 * listener NotificarStockMinimo sobre el evento StockMinimoAlcanzado.
 */
class StockMinimoBajo extends Notification
{
    use Queueable;

    public function __construct(private readonly Insumo $insumo) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * Carga mínima que se guarda y se difunde: tipo, mensaje corto, código del
     * insumo y fecha. Sin costos ni el modelo completo. La fecha se incluye en el
     * arreglo para que viaje también por WebSocket (el broadcast no tiene
     * created_at).
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'tipo' => 'stock_minimo',
            'mensaje' => "Stock bajo: {$this->insumo->nombre} ({$this->insumo->existencia}/{$this->insumo->stock_minimo})",
            'codigo' => $this->insumo->codigo,
            'fecha' => now()->toISOString(),
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
