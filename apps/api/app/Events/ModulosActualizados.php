<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

// Se emite cuando cambia alguna bandera de módulo. Viaja por un canal privado
// a cualquier usuario con sesión, para que el panel se actualice sin recargar.
class ModulosActualizados implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;

    /**
     * @param  array<string, bool>  $modulos
     */
    public function __construct(public array $modulos) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('modulos');
    }

    public function broadcastAs(): string
    {
        return 'modulos.actualizados';
    }

    /**
     * @return array<string, array<string, bool>>
     */
    public function broadcastWith(): array
    {
        return ['modulos' => $this->modulos];
    }
}
