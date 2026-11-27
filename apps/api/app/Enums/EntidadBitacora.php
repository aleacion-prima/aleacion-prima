<?php

declare(strict_types=1);

namespace App\Enums;

enum EntidadBitacora: string
{
    case Usuario = 'usuario';
    case Producto = 'producto';
    case Insumo = 'insumo';
    case Area = 'area';
    case Modulo = 'modulo';
}
