<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

// Se emite cuando la existencia de un insumo llega a su stock mínimo o queda
// por debajo. Por ahora solo es el evento; la notificación al administrador se
// conecta en el Sprint 3 (T-32).
class StockMinimoAlcanzado
{
    use Dispatchable;

    public int $insumoId;

    public float $existencia;

    public float $stockMinimo;

    public function __construct(int $insumoId, float $existencia, float $stockMinimo)
    {
        $this->insumoId = $insumoId;
        $this->existencia = $existencia;
        $this->stockMinimo = $stockMinimo;
    }
}
