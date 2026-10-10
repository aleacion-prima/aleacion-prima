<?php

declare(strict_types=1);

namespace App\Enums;

enum TipoMovimientoInsumo: string
{
    case Entrada = 'entrada';
    case Asignacion = 'asignacion';
    case Devolucion = 'devolucion';
    case Retorno = 'retorno';
    case Ajuste = 'ajuste';
}
