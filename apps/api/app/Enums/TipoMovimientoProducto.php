<?php

declare(strict_types=1);

namespace App\Enums;

enum TipoMovimientoProducto: string
{
    case Produccion = 'produccion';
    case Venta = 'venta';
    case Ajuste = 'ajuste';
}
