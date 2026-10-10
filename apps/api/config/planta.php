<?php

/*
|--------------------------------------------------------------------------
| Constantes de la planta
|--------------------------------------------------------------------------
| Describen a esta planta (un horno, un turno) y no cambian en operación.
| Ver docs/modelo-datos.md, "Constantes de planta" y decisión 18.
*/

return [
    // Minutos entre ocurrido_en y registrado_en a partir de los cuales
        // la bitácora marca el movimiento como captura tardía.
        'umbral_captura_tardia' => (int) env('PLANTA_UMBRAL_CAPTURA_TARDIA', 30),

        // Capacidad del horno por carga. Un lote es una colada:
        // cantidad_objetivo × metal por pieza ≤ este valor.
        'capacidad_colada_kg' => (float) env('PLANTA_CAPACIDAD_COLADA_KG', 750),

        // Código del insumo al que regresan los retornos de metal (ADR-0012).
        'insumo_retorno' => env('PLANTA_INSUMO_RETORNO', 'CHT-FUN'),
];
