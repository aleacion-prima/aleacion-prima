<?php

namespace App\Enums;

enum Semaforo: string
{
    case Verde = 'verde';
    case Ambar = 'ambar';
    case Rojo = 'rojo';

    /** Sirve para quedarse con el peor color entre varias señales. */
    public function gravedad(): int
    {
        return match ($this) {
            self::Verde => 0,
            self::Ambar => 1,
            self::Rojo => 2,
        };
    }
}
