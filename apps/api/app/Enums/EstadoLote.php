<?php

declare(strict_types=1);

namespace App\Enums;

enum EstadoLote: string
{
    case Creado = 'creado';
    case EnProceso = 'en_proceso';
    case PorConfirmar = 'por_confirmar';
    case Terminado = 'terminado';
    case Cancelado = 'cancelado';
}
