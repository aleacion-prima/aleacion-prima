<?php

namespace App\Events;

use App\Models\Producto;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DisponibilidadActualizada implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Producto $producto) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [new Channel('catalogo')];
    }

    public function broadcastAs(): string
    {
        return 'disponibilidad.actualizada';
    }

    /**
     * @return array{id: int, disponible: int}
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->producto->id,
            'disponible' => (int) $this->producto->existencia,
        ];
    }
}
