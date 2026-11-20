<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Un encargado registró un imprevisto. Solo lleva datos simples (no el documento de Mongo)
 * para que el tablero decida si recarga el lote.
 */
class ImprevistoRegistrado implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public string $codigoLote,
        public string $imprevistoId,
        public int $areaId,
        public string $tipo,
        public bool $resuelto,
    ) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('tablero')];
    }

    public function broadcastAs(): string
    {
        return 'imprevisto.registrado';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'codigo' => $this->codigoLote,
            'imprevisto_id' => $this->imprevistoId,
            'area_id' => $this->areaId,
            'tipo' => $this->tipo,
            'resuelto' => $this->resuelto,
        ];
    }
}
