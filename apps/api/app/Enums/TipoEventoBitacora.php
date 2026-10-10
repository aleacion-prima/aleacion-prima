<?php

declare(strict_types=1);

namespace App\Enums;

enum TipoEventoBitacora: string
{
    case Creacion = 'creacion';
    case AsignacionInsumo = 'asignacion_insumo';
    case EntradaArea = 'entrada_area';
    case SalidaArea = 'salida_area';
    case Imprevisto = 'imprevisto';
    case Retorno = 'retorno';
    case DevolucionInsumo = 'devolucion_insumo';
    case Cancelacion = 'cancelacion';
    case Confirmacion = 'confirmacion';
    case Ajuste = 'ajuste';
    case StockMinimo = 'stock_minimo';
}
