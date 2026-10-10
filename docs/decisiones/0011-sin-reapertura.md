# ADR-0011: Un lote terminado no se reabre; se corrige con ajustes

- **Estado:** aceptada (pendiente de lectura del equipo antes del Sprint 0)

- **Relacionados:** [modelo-datos.md](../modelo-datos.md) (decisión 8, tabla `ajustes`), ADR-0003 (bitácora), ADR-0010, riesgo RE-04

## Contexto

Después de confirmar un lote, alguien puede descubrir que una captura estuvo mal: una merma que no era, una hora equivocada, un retorno que se pesó mal. Hay que decidir cómo se corrige sin perder la trazabilidad que es el argumento del sistema.

Opciones:

1. **Reabrir** el lote (volver a `en_proceso` o a `por_confirmar`), editar y volver a confirmar. Cada reapertura reescribe historia: el costo congelado cambia sin dejar rastro de cuánto valía antes ni por qué, y el inventario publicado oscila.
2. **Editar en sitio** las filas de `lote_areas` de un lote terminado, con log. Es reabrir sin decirlo.
3. **Registrar ajustes**: el dato original se conserva, el ajuste dice qué cambió, de qué valor a cuál, por qué y quién lo hizo, y a partir de él se recalcula lo derivado.

## Decisión

La opción 3.

- Un lote en `terminado` (o `cancelado`) **no cambia de estado hacia atrás** y sus filas de `lote_areas` y `lote_insumos` no se editan directamente.
- Toda corrección es una fila en **`ajustes`**: objetivo (una sola FK: `lote_area_id`, `lote_insumo_id`, `insumo_id` o `producto_id`), `campo`, `valores { anterior, nuevo }`, `motivo` obligatorio y `user_id` (solo administrador).
- Al registrar el ajuste, la API aplica el valor nuevo en la fila objetivo, recalcula los costos del lote y, si cambió `piezas_entregadas` de la última parada, corrige `piezas_terminadas` y crea el `movimientos_producto` de tipo `ajuste` correspondiente. Todo queda en la bitácora.
- Es el **mismo mecanismo** que los ajustes de inventario que pide RE-04: se implementa una vez y sirve para lotes, insumos y productos.

## Consecuencias

- La historia es reconstruible: el valor original, cada corrección, su motivo y su responsable están en `ajustes` y en la bitácora; el costo actual es siempre "original + ajustes".
- El costo congelado puede cambiar después de confirmar, pero solo por un ajuste explícito y auditable, nunca por una edición silenciosa.
- Costo: un formulario de ajuste es más ceremonia que editar un campo. Es intencional; la fricción es la garantía.
- Si aparece un quinto tipo de objetivo ajustable, se agrega la columna y se actualiza el `CHECK` en una migración (decisión 8 del modelo).
