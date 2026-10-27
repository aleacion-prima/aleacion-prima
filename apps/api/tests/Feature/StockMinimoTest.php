<?php

use App\Events\StockMinimoAlcanzado;
use App\Inventario\DetectorStockMinimo;
use Illuminate\Support\Facades\Event;

it('emite StockMinimoAlcanzado cuando la existencia llega al mínimo', function () {
    Event::fake();

    // Un movimiento baja la existencia de 10 a 5, y el mínimo es 5.
    (new DetectorStockMinimo)->revisar(
        insumoId: 1,
        existenciaAnterior: 10.0,
        existenciaNueva: 5.0,
        stockMinimo: 5.0,
    );

    Event::assertDispatched(StockMinimoAlcanzado::class, function (StockMinimoAlcanzado $evento) {
        return $evento->insumoId === 1
            && $evento->existencia === 5.0
            && $evento->stockMinimo === 5.0;
    });
});

it('también emite el evento cuando la existencia queda por debajo del mínimo', function () {
    Event::fake();

    (new DetectorStockMinimo)->revisar(
        insumoId: 7,
        existenciaAnterior: 8.0,
        existenciaNueva: 2.0,
        stockMinimo: 5.0,
    );

    Event::assertDispatched(StockMinimoAlcanzado::class);
});

it('no emite el evento si la existencia sigue por encima del mínimo', function () {
    Event::fake();

    (new DetectorStockMinimo)->revisar(
        insumoId: 1,
        existenciaAnterior: 20.0,
        existenciaNueva: 12.0,
        stockMinimo: 5.0,
    );

    Event::assertNotDispatched(StockMinimoAlcanzado::class);
});

it('no repite el aviso cuando la existencia ya venía por debajo del mínimo', function () {
    Event::fake();

    // Ya estaba en 4 (abajo del mínimo 5) y baja a 3: no debe volver a avisar.
    (new DetectorStockMinimo)->revisar(
        insumoId: 1,
        existenciaAnterior: 4.0,
        existenciaNueva: 3.0,
        stockMinimo: 5.0,
    );

    Event::assertNotDispatched(StockMinimoAlcanzado::class);
});
