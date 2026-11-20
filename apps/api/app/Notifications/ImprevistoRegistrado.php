<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

/**
 * Aviso al administrador de un imprevisto registrado en un lote. La dispara T-43
 * cuando se registre un imprevisto: recibe solo el código del lote y el mensaje,
 * sin acoplarse al dominio de imprevistos (que aún no existe).
 */
class ImprevistoRegistrado extends Notification
{
    use Queueable;

    public function __construct(private readonly string $codigo, private readonly string $mensaje) {}

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
            'tipo' => 'imprevisto',
            'mensaje' => $this->mensaje,
            'codigo' => $this->codigo,
            'fecha' => now()->toISOString(),
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
