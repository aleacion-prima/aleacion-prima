<?php

namespace App\Events;

use App\Models\Lote;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ImprevistoActualizado implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Lote $lote,
        public string $imprevistoId,
        public bool $resuelto,
    ) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        $areas = $this->lote->paradas()->pluck('area_id')->unique()->values();

        return [
            new PrivateChannel('tablero'),
            ...$areas->map(fn ($area) => new PrivateChannel("area.{$area}"))->all(),
        ];
    }

    public function broadcastAs(): string
    {
        return 'imprevisto.actualizado';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'codigo' => $this->lote->codigo,
            'imprevisto_id' => $this->imprevistoId,
            'resuelto' => $this->resuelto,
        ];
    }
}
