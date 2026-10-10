<?php

declare(strict_types=1);

namespace App\Enums;

enum EstadoPago: string
{
    case Pendiente = 'pendiente';
    case Parcial = 'parcial';
    case Pagado = 'pagado';
}
