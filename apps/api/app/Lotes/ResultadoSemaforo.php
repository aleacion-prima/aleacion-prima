<?php

namespace App\Lotes;

use App\Enums\Semaforo;

final readonly class ResultadoSemaforo
{
    /**
     * @param  list<string>  $motivos  Del más grave al menos grave. Vacía si el lote está en verde.
     */
    public function __construct(
        public Semaforo $semaforo,
        public array $motivos,
    ) {}
}
