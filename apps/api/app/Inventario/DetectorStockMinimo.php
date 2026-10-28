<?php

namespace App\Inventario;

use App\Events\StockMinimoAlcanzado;

// Revisa, después de un movimiento que cambió la existencia de un insumo, si
// acaba de llegar a su stock mínimo (o por debajo). Avisa una sola vez: solo
// en el momento en que la existencia cruza el mínimo hacia abajo.
//
// Integración (otro issue de T1.4): el registro de movimientos, después de
// recalcular la existencia del insumo, debe llamar:
//   (new DetectorStockMinimo())->revisar(
//       $insumo->id, $existenciaAnterior, $existenciaNueva, $insumo->stock_minimo
//   );
// NO implementar ese registro de movimientos aquí.
class DetectorStockMinimo
{
    public function revisar(int $insumoId, float $existenciaAnterior, float $existenciaNueva, float $stockMinimo): void
    {
        // Antes del movimiento la existencia todavía estaba por encima del mínimo.
        $estabaArriba = $existenciaAnterior > $stockMinimo;

        // Después del movimiento quedó igual o por debajo del mínimo.
        $ahoraEstaAbajo = $existenciaNueva <= $stockMinimo;

        // Solo avisamos en el cruce hacia abajo. Si ya venía por debajo, no se
        // repite el aviso aunque la existencia baje más.
        if ($estabaArriba && $ahoraEstaAbajo) {
            StockMinimoAlcanzado::dispatch($insumoId, $existenciaNueva, $stockMinimo);
        }
    }
}
