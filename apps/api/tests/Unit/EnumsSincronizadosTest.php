<?php

declare(strict_types=1);

use App\Enums\CategoriaInsumo;
use App\Enums\EstadoLote;
use App\Enums\EstadoPago;
use App\Enums\EstadoPedido;
use App\Enums\Rol;
use App\Enums\TipoEventoBitacora;
use App\Enums\TipoImprevisto;
use App\Enums\TipoMovimientoInsumo;
use App\Enums\TipoMovimientoProducto;

// Espejo de package/types
$pares = [
    'ROLES' => ['ROLES', Rol::class],
    'ESTADOS_LOTE' => ['ESTADOS_LOTE', EstadoLote::class],
    'CATEGORIAS_INSUMO' => ['CATEGORIAS_INSUMO', CategoriaInsumo::class],
    'TIPOS_MOVIMIENTO_INSUMO' => ['TIPOS_MOVIMIENTO_INSUMO', TipoMovimientoInsumo::class],
    'TIPOS_MOVIMIENTO_PRODUCTO' => ['TIPOS_MOVIMIENTO_PRODUCTO', TipoMovimientoProducto::class],
    'ESTADOS_PEDIDO' => ['ESTADOS_PEDIDO', EstadoPedido::class],
    'ESTADOS_PAGO' => ['ESTADOS_PAGO', EstadoPago::class],
    'TIPOS_IMPREVISTO' => ['TIPOS_IMPREVISTO', TipoImprevisto::class],
    'TIPOS_EVENTO_BITACORA' => ['TIPOS_EVENTO_BITACORA', TipoEventoBitacora::class],
];

/**
 * Extrae los literales de `export const NOMBRE = ['a', 'b'] as const; `.
 *
 * @return list<string>
 */
function valoresTs(string $fuente, string $constante): array
{
    $patron = '/export const '.$constante.' = \[(.*?)\] as const;/s';
    preg_match($patron, $fuente, $coincidencia);
    preg_match_all("/'([^']+)'/", $coincidencia[1] ?? '', $valores);

    return $valores[1];
}

it('mantiene los enums PHP iguales a los arreglos de packages/types', function (string $constante, string $enum) {
    $fuente = file_get_contents(dirname(__DIR__, 4).'/packages/types/src/index.ts');
    $valoresPhp = array_map(fn ($caso) => $caso->value, $enum::cases());

    expect($valoresPhp)->toBe(valoresTs($fuente, $constante));
})->with($pares);
