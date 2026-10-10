# ADR-0010: El cierre lo dispara la última parada, pero cuenta cuando el administrador confirma

- **Estado:** aceptada (pendiente de lectura del equipo antes del Sprint 0)

- **Relacionados:** [flujo-lote.md](../flujo-lote.md), [modelo-datos.md](../modelo-datos.md), ADR-0005 (canal público), riesgo RE-04 del anteproyecto

## Contexto

Al registrar la salida de la última parada, las piezas terminadas deberían entrar al inventario y publicarse como disponibles en el sitio. Pero esa captura la hace el encargado del área, en el momento y sin revisión, y un error ahí (un cero de más, una parada olvidada) publicaría disponibilidad falsa a clientes y congelaría un costo equivocado. El anteproyecto lo identifica como RE-04.

Opciones:

1. **Cierre automático** al salir de la última parada. Rápido, sin revisión.
2. **Cierre automático con ventana de corrección** (por ejemplo, 24 h en las que se puede editar). Añade un estado temporal ambiguo y las piezas ya estarían publicadas.
3. **Estado intermedio `por_confirmar`** y confirmación explícita del administrador.

## Decisión

La opción 3.

- La salida de la última parada pasa el lote a **`por_confirmar`**. En ese estado el lote no cuenta en inventario, no emite `disponibilidad.actualizada` y su costo se muestra como "a la fecha".
- El **administrador confirma** desde el panel. Al confirmar, en una sola transacción: el lote pasa a `terminado`, se fijan `piezas_terminadas` (las entregadas por la última parada), se crea el `movimientos_producto` de tipo `produccion`, se recalcula y congela el costo (`costo_por_pieza` incluido) y se emite el evento público con `{ id, disponible }`.
- El administrador puede, antes de confirmar, **corregir capturas** de las paradas directamente (el lote aún no está terminado, así que no requiere ajuste formal) o **cancelar** el lote.
- Los lotes en `por_confirmar` generan una notificación al administrador y aparecen destacados en el tablero.

## Consecuencias

- Un humano revisa antes de que algo sea visible para clientes o se convierta en costo definitivo. Es una parada más en el flujo, pero es la que evita RE-04.
- Riesgo: lotes que se quedan en `por_confirmar` porque nadie confirma. Mitigación: notificación y destaque en el tablero; si se vuelve un problema, se agrega un recordatorio diario, no se automatiza el cierre.
- El inventario de producto terminado siempre refleja piezas confirmadas, nunca capturas pendientes de revisión.
