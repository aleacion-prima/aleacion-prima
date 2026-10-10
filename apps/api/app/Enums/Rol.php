<?php

declare(strict_types=1);

namespace App\Enums;

enum Rol: string
{
    case Administrador = 'administrador';
    case Ventas = 'ventas';
    case Encargado = 'encargado';
    case Cliente = 'cliente';
}
