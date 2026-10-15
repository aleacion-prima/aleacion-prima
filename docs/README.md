# Documentación

Qué hay en esta carpeta y cuándo leer cada cosa. Para instalar y trabajar día a día, el [README](../README.md) de la raíz; para las reglas de los asistentes de IA, [AGENTS.md](../AGENTS.md).

| Archivo                                                 | Qué contiene                                                                                   | Léelo cuando…                                                      |
| ------------------------------------------------------- | ---------------------------------------------------------------------------------------------- | ------------------------------------------------------------------ |
| [arquitectura.md](./arquitectura.md)                    | Piezas del sistema, cómo se comunican, entornos, despliegue. Con diagramas.                    | Entras al proyecto o vas a tocar algo que cruza dos apps           |
| [autenticacion.md](./autenticacion.md)                  | Cómo entran el panel, la app y el sitio; roles; variables por entorno; cómo verificar          | Vas a hacer login, rutas protegidas, canales privados o CORS       |
| [diseno.md](./diseno.md)                                | Guía visual: paleta, colores por estado, reglas de contraste y tipografías                     | Vas a maquetar en Figma o a dar estilo al panel, la app o el sitio |
| [empresa.md](./empresa.md)                              | Contexto de la empresa, áreas, productos, insumos y ejemplos de lotes                          | Necesitas datos de dominio para implementar o probar flujos        |
| [flujo-lote.md](./flujo-lote.md)                        | Recorrido del lote, estados, qué se captura en cada área, costo real, ajustes, código del lote | Vas a trabajar en lotes, áreas, inventario o costos                |
| [glosario.md](./glosario.md)                            | Términos de fundición y del sistema (colada, lote del proveedor, parada, retorno)              | Necesitas aclarar vocabulario del dominio                          |
| [modelo-datos.md](./modelo-datos.md)                    | Diccionario de datos (PostgreSQL y MongoDB), validaciones y decisiones de datos                | Vas a crear o modificar modelos, migraciones o persistencia        |
| [requerimientos.md](./requerimientos.md)                | Requerimientos funcionales y no funcionales (sección 8 del anteproyecto)                       | Necesitas saber qué debe hacer el sistema y con qué restricciones  |
| [wireframes/](./wireframes/)                            | Wireframes en HTML: panel y sitio (`panel-y-sitio.html`) y app móvil (`app-movil.html`)        | Vas a maquetar o implementar una pantalla                          |
| [decisiones/](./decisiones/README.md)                   | Registros de decisión (ADR): qué se decidió, por qué y qué se descartó                         | Te preguntas "¿por qué no usamos X?" o vas a cambiar una base      |
| [ADR-0009](./decisiones/0009-recorrido-por-lote.md)     | Recorrido definido por producto y congelado en el lote                                         | Vas a cambiar el recorrido de producción                           |
| [ADR-0010](./decisiones/0010-confirmacion-de-cierre.md) | Confirmación administrativa del cierre del lote                                                | Vas a cambiar el cierre, inventario terminado o publicación        |
| [ADR-0011](./decisiones/0011-sin-reapertura.md)         | Correcciones de lotes terminados mediante ajustes                                              | Vas a implementar ajustes o corregir lotes cerrados                |
| [ADR-0012](./decisiones/0012-retornos.md)               | Retornos de metal y su efecto en inventario/costo                                              | Vas a tocar desmolde, retornos o costo de material                 |
| [ADR-0014](./decisiones/0014-github-flow-squash.md)     | Ramas, commits, Pull Requests y Squash and merge                                               | Vas a abrir tu primera rama o tu primer PR                         |

## Diagramas

Fuentes en PlantUML; se exportan a SVG en [`diagramas/img/`](./diagramas/img/) con `pnpm diagramas` (PlantUML en Docker), y esos SVG son los que muestra el README de la raíz.

- [arquitectura.puml](./diagramas/arquitectura.puml)
- [mapa-navegacion.puml](./diagramas/mapa-navegacion.puml)
- [modelo-datos.puml](./diagramas/modelo-datos.puml) (vista general)
- [der-catalogos.puml](./diagramas/der-catalogos.puml)
- [der-lotes.puml](./diagramas/der-lotes.puml)
- [der-ventas.puml](./diagramas/der-ventas.puml)
- [casos-de-uso-administracion.puml](./diagramas/casos-de-uso/casos-de-uso-administracion.puml)
- [casos-de-uso-lotes.puml](./diagramas/casos-de-uso/casos-de-uso-lotes.puml)
- [casos-de-uso-movil-sitio.puml](./diagramas/casos-de-uso/casos-de-uso-movil-sitio.puml)

## Reglas de la carpeta

- **Estos documentos describen lo decidido, no lo deseado.** Lo que todavía se discute se marca como _propuesta_ o _pendiente_; cuando el equipo lo resuelve, se actualiza el archivo y, si es una decisión difícil de revertir, se escribe un ADR.
- Cambios de documentación van en su propio commit (`docs: ...`) o junto con el código que los motiva, pero siempre en el mismo PR.
- Diagramas en Mermaid dentro del markdown (GitHub los renderiza). Los que también van en el anteproyecto o en el README de la raíz se mantienen en PlantUML en `diagramas/` y se exportan a SVG en `diagramas/img/`, para que queden idénticos en todos lados.
- Sin fechas en los ADR: el número da el orden y `git log` la fecha.
- El anteproyecto se entregó en Google Docs y se enlaza desde el README de la raíz (Unidad 1); su parte técnica vive aquí en Markdown y es la que se mantiene conforme avanza el desarrollo. Si el anteproyecto y un archivo de esta carpeta se contradicen, manda esta carpeta y se anota la diferencia.
