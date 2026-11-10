<?php

namespace App\Lotes;

use App\Events\LoteActualizado;

class RecalcularCostoAlActualizar
{
    public function __construct(private CalculadorCostoLote $calculador) {}

    public function handle(LoteActualizado $evento): void
    {
        $this->calculador->recalcular($evento->lote);
    }
}
