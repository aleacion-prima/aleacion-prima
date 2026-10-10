<?php

namespace App\Enums;

enum EstadoLote: string
{
    case Creado = 'creado';
    case EnProceso = 'en_proceso';
    case PorConfirmar = 'por_confirmar';
    case Cancelado = 'cancelado';
    case Terminado = 'terminado';
}
