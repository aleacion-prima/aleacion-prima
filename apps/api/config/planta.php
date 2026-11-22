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

    'area_retorno' => env('PLANTA_AREA_RETORNO', 'DES'),

    'zona_horaria' => env('PLANTA_ZONA_HORARIA', 'America/Mexico_City'),

    // Semáforo del lote (verde, ámbar, rojo).
    // Un umbral se cumple cuando el valor es igual o mayor, salvo el de minutos de un imprevisto abierto, que se cumple al pasarse.
    'semaforo' => [
        // Merma acumulada como porcentaje de la cantidad objetivo.
        'merma_ambar_pct' => 5,
        'merma_rojo_pct' => 10,

        // Imprevistos sin resolver: cuántos abiertos ponen ámbar y cuántos
        // minutos abierto pone rojo.
        'imprevistos_ambar_abiertos' => 1,
        'imprevisto_rojo_minutos' => 120,

        // Costo por pieza proyectado como porcentaje del precio de mayoreo.
        'costo_ambar_pct' => 80,
        'costo_rojo_pct' => 100,

        // Capturas tardías acumuladas que ponen ámbar. Nunca ponen rojo.
        'capturas_tardias_ambar' => 2,
    ],
];
