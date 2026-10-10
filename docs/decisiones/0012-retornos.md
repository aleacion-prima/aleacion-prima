# ADR-0012: Los retornos de metal vuelven al inventario y se restan del costo del lote

- **Estado:** aceptada (pendiente de lectura del equipo antes del Sprint 0)
- **Fecha:** 2026-09-29
- **Relacionados:** [modelo-datos.md](../modelo-datos.md) (decisión 14, `lote_areas.retorno_kg`, `movimientos_insumo.tipo = retorno`), [empresa.md](../empresa.md) (secciones 2 y 7), [flujo-lote.md](../flujo-lote.md), ADR-0003

## Contexto

En una fundición no todo el metal que se cuela acaba siendo pieza. Por cada kilo de pieza se cargan alrededor de 1.36 kg de metal: el sistema de alimentación (canales y mazarotas) rinde 75 % y el horno pierde 2 % en oxidación y escoria. Lo que sobra al desmoldar (canales, mazarotas y piezas rotas) es entre 15 % y 25 % del metal cargado, se separa en Desmolde y **vuelve al horno como chatarra de fundición en el siguiente lote**.

El modelo original solo conocía dos destinos para el material asignado a un lote: consumido (cuenta en el costo) o devuelto al cancelar antes de la primera parada. Con eso, los retornos habrían quedado como merma:

- El costo por pieza se infla justo en el dato que el sistema promete medir. En el lote 1 de `empresa.md` (50 discos), contar el retorno como merma da $250.77 por pieza; restarlo da $226.11. Es un 11 % de diferencia en la cifra que Ventas usa para cotizar.
- El inventario de chatarra de fundición se desincroniza de la realidad física: cada lote regresa físicamente 100–160 kg que el sistema no vería, y el stock mínimo dispararía alertas falsas.

Opciones:

1. **Contar el retorno como merma** y aceptar el sesgo. Cero cambios al modelo; costo sistemáticamente alto e inventario de chatarra siempre por debajo del real.
2. **Descontar un porcentaje fijo de retorno** en la receta (cargar menos metal en la asignación). Simple, pero falso: el metal sí se carga y sí se paga; y el porcentaje real varía por pieza y por lote.
3. **Capturar el retorno real en Desmolde**, devolverlo al inventario como un movimiento propio y restarlo del costo de material del lote.

## Decisión

La opción 3.

- `lote_areas` tiene una columna **`retorno_kg`** (decimal, null). Se captura en la salida de la parada que separa piezas de alimentación (en Aleación Prima, `DES`); en las demás paradas queda en null.
- Al guardar esa salida, la API crea un **`movimientos_insumo` de tipo `retorno`**: cantidad positiva, `lote_id` del lote, sobre el insumo configurado en `planta.insumo_retorno` (`CHT-FUN`), con `costo_unitario` = costo vigente de ese insumo en ese momento. `insumos.existencia` sube en la misma transacción.
- **`CalculadorCostoLote` resta del material** la suma de los movimientos `retorno` del lote (`cantidad × costo_unitario`). El retorno no es una parada ni un componente aparte del costo: es material que el lote no consumió.
- El retorno se valora al costo vigente de la chatarra, no al costo promedio del metal cargado: lo que regresa _es_ chatarra de fundición, no acero 1020 ni ferroaleaciones, y es lo que costaría comprarla.
- Las **piezas rechazadas después de Desmolde** (en `GRA` o `CAL`) son merma del lote (`merma_piezas`). Físicamente también vuelven al horno, pero se registran con una `entrada` manual de chatarra cuando se reprocesan, fuera del costo del lote que las produjo: ese lote sí pagó por hacerlas mal.
- `retorno_kg` es **ajustable** como cualquier otra columna de `lote_areas` (ADR-0011); el ajuste crea el `movimientos_insumo` de tipo `ajuste` por la diferencia y recalcula el costo.
- Validación: `retorno_kg ≤ Σ` metal asignado al lote (`lote_insumos` de insumos con `categoria = metal`).

## Consecuencias

- El costo por pieza refleja el metal que realmente se quedó en las piezas. Es la cifra que la empresa hoy no tiene.
- El inventario de chatarra de fundición sigue la realidad física; el stock mínimo tiene sentido.
- Una captura más para el encargado de Desmolde (pesar los retornos). Es la única captura nueva que este ADR introduce y es un dato que la planta ya obtiene al separar.
- El costo de material de un lote **baja** en Desmolde. El tablero muestra "costo a la fecha", así que los usuarios verán que el material sube al crear el lote y baja al desmoldar; hay que explicarlo en la interfaz una vez (un tooltip en el desglose de costo), no ocultarlo.
- Un tipo nuevo en el enum `TipoMovimientoInsumo` (`packages/types` y PHP), una columna en `lote_areas`, una resta en el calculador y una clave de configuración. Nada de eso es difícil de quitar si el equipo lo rechaza; lo difícil de revertir es haber calculado costos sin él.
- Si otro giro no tiene retornos, `retorno_kg` simplemente no se captura y la resta es cero; el modelo no cambia.
