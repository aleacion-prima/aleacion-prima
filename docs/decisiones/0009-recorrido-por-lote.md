# ADR-0009: El recorrido se define por producto y se congela en cada lote

- **Estado:** aceptada (pendiente de lectura del equipo antes del Sprint 0)

- **Relacionados:** [flujo-lote.md](../flujo-lote.md), [modelo-datos.md](../modelo-datos.md) (decisiones 1 y 17), [empresa.md](../empresa.md)

## Contexto

Cada lote pasa por una secuencia de áreas y el costo real se calcula sumando las horas de cada parada por sus tarifas. Hay que decidir de dónde sale esa secuencia y qué pasa cuando un lote necesita una distinta (la carcasa de precisión no se granalla; un lote puede necesitar empaque o retrabajo).

Tres opciones se consideraron:

1. **Recorrido global** fijo para toda la planta. No sirve: los productos no pasan por las mismas áreas.
2. **Recorrido por producto, leído del catálogo en cada consulta.** Si el producto cambia de recorrido, los lotes abiertos y los históricos cambian con él y el costo deja de ser reproducible.
3. **Recorrido por producto como plantilla, copiado al lote al crearlo y ajustable en ese momento.**

## Decisión

La opción 3.

- `producto_area` guarda el recorrido **por defecto** de cada producto (`area_id`, `orden`).
- Al crear un lote, la API copia ese recorrido a `lote_areas` (una fila por parada, con las tarifas del área congeladas). Quien crea el lote puede **quitar, agregar, reordenar o repetir** paradas antes de guardar; lo que se guarda es el recorrido de _ese_ lote y ya no se vuelve a leer del producto.
- Todo recorrido, de producto o de lote, debe incluir las **áreas obligatorias** activas (`areas.obligatoria`) en el orden relativo del catálogo (`areas.orden_catalogo`). Las opcionales van donde se pongan. En Aleación Prima las obligatorias son `MOL → FUS → COL → DES`.
- El recorrido del lote es **inmutable** una vez creado. Si hace falta cambiarlo con el lote en marcha (un retrabajo no previsto), se agrega una parada al final con un ajuste con motivo y responsable; no se reordena lo ya recorrido.

## Consecuencias

- El costo de un lote es reproducible: sus paradas y tarifas están en sus propias filas, no dependen del catálogo.
- Cambiar el recorrido de un producto afecta solo a lotes nuevos.
- Un componente único (`RecorridoTimeline.vue`) sirve para definir el recorrido del producto, ajustarlo al crear el lote y mostrar el avance, porque los tres usan la misma estructura `{ area_id, orden }[]`.
- Costo: duplicación de datos (el recorrido vive en `producto_area` y en cada `lote_areas`) y una validación más en la API. Aceptado.
- Queda abierto si las opcionales deben tener una posición mínima (por ejemplo, siempre después de `DES`). Para el MVP no se restringe.
