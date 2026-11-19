<?php

namespace App\Notifications;

use App\Models\Lote;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

/**
 * Aviso al administrador de que un lote terminó su recorrido y quedó por
 * confirmar. La dispara el listener NotificarLotePorConfirmar sobre
 * LoteActualizado cuando el lote entra al estado por_confirmar.
 */
class LotePorConfirmar extends Notification
{
    use Queueable;

    public function __construct(private readonly Lote $lote) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'tipo' => 'lote_por_confirmar',
            'mensaje' => "Lote {$this->lote->codigo} listo para confirmar",
            'codigo' => $this->lote->codigo,
            'fecha' => now()->toISOString(),
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
