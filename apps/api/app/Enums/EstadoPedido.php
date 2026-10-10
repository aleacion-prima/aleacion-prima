<?php

declare(strict_types=1);

namespace App\Enums;

enum EstadoPedido: string
{
    case Borrador = 'borrador';
    case Confirmado = 'confirmado';
    case Entregado = 'entregado';
    case Cancelado = 'cancelado';
}
