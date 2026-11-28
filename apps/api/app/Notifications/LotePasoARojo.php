<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

/**
 * Aviso al administrador de que un lote pasó a rojo en el semáforo. La dispara el
 * listener NotificarLoteRojo sobre el evento LoteActualizado, solo en la
 * transición a rojo (no mientras siga en rojo).
 */
class LotePasoARojo extends Notification
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
     * Carga mínima que se guarda y se difunde: tipo, mensaje corto, código del
     * lote y fecha. Sin el modelo completo. La fecha se incluye en el arreglo
     * para que viaje también por WebSocket (el broadcast no tiene created_at).
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'tipo' => 'lote_rojo',
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
