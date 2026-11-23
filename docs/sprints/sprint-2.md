# Sprint 2 — Flujo del lote, Figma y recuperación de acceso

- **Sprint:** 2 (26 de octubre al 8 de noviembre de 2026)
- **Objetivo:** dejar en `main` el flujo completo del lote (crear, capturar por área, cerrar y costear), la recuperación de contraseña, el panel fiel a Figma, los módulos con feature flags y la prueba E2E del lote de referencia.
- **Equipo:** Humberto Castro Cortes (Product Owner), Cesar Leonel Morillon González (Scrum Master), Julio Alberto López Castro y Paloma Johana Portillo Hernández.
- **Compromiso:** 15 tareas, 46 puntos.

## Resumen

### Planeado y terminado

| Tarea                                             | Issue | PR  | Puntos | Responsable | Terminada  |
| ------------------------------------------------- | ----- | --- | ------ | ----------- | ---------- |
| T-18 Crear lote                                   | #35   | #55 | 5      | Cesar       | 2026-11-08 |
| T-19 Ciclo de vida del lote                       | #36   | #57 | 5      | Cesar       | 2026-11-08 |
| T-20 Captura por área                             | #37   | #56 | 5      | Cesar       | 2026-11-08 |
| T-21 Cálculo de costos del lote                   | #38   | #65 | 3      | Paloma      | 2026-11-10 |
| T-22 Recuperación de contraseña por correo        | #39   | #52 | 3      | Julio       | 2026-11-03 |
| T-23 Pregunta secreta como recuperación           | #40   | #53 | 3      | Julio       | 2026-11-03 |
| T-24 Acta del Sprint 1 y guía de conflictos       | #41   | #61 | 2      | Humberto    | 2026-11-10 |
| T-25 Estilos globales del panel                   | #42   | #58 | 3      | Cesar       | 2026-11-08 |
| T-26 Usuarios e inventarios fieles a Figma        | #43   | #60 | 3      | Humberto    | 2026-11-08 |
| T-27 Áreas, productos e insumos fieles a Figma    | #44   | #62 | 3      | Paloma      | 2026-11-10 |
| T-28 Correcciones al Figma                        | #45   | —   | 2      | Paloma      | 2026-11-09 |
| T-31 Deuda de productos                           | #48   | #63 | 2      | Paloma      | 2026-11-10 |
| T-32 Deploy manual desde Actions                  | #49   | #59 | 1      | Julio       | 2026-11-07 |
| T-33 Prueba E2E del lote de referencia            | #50   | #66 | 3      | Julio       | 2026-11-10 |
| T-34 Feature flags con Pennant y módulo de ventas | #51   | #64 | 3      | Julio       | 2026-11-10 |

Dos diferencias con la columna «Linked pull requests» del tablero: #42 muestra #57, porque ese PR también declaraba `Closes #42` aunque los estilos son de #58; y #50 aparece sin PR porque #66 no llevaba `Closes #50`. La tabla usa el PR que hizo el trabajo de cada tarea.

### Agregado durante el sprint

Ninguna tarea fuera de la planeación. Las 15 tareas se crearon el 31 de octubre, ya iniciada la iteración, y no se agregó ninguna después.

### Resultado

Se cerraron **las 15 tareas comprometidas: 46 de 46 puntos**. No hubo tareas que pasar al siguiente sprint.

El trabajo dejó en `main` el flujo del lote de punta a punta: creación con recorrido, receta y costos congelados; captura de entrada y salida por área con actualización en tiempo real; confirmación de cierre y cancelación; y cálculo del costo con un comando de recálculo. Además entraron la recuperación de contraseña por correo y por pregunta secreta, el panel alineado con Figma (con correcciones al propio Figma), la primera prueba E2E del lote de referencia, el deploy manual desde Actions y los feature flags de módulos.

El tablero mantiene las 15 tareas dentro del Sprint 2 aunque **seis se cerraron el 10 de noviembre**, dos días después de la fecha de la iteración: #38, #41, #44, #48, #50 y #51.

## Retrospectiva

### Salió bien

- **Se cumplió el compromiso completo.** 15 de 15 tareas y 46 de 46 puntos, sin arrastrar deuda al siguiente sprint.
- **El flujo del lote quedó completo y probado de punta a punta.** T-18 a T-21 se integraron en el mismo sprint y la prueba E2E (#66) recorre el lote de referencia hasta el cierre con su costo por pieza, y el deploy manual desde Actions permitió publicar sin esperar a un merge a `main`.
- **La recuperación de acceso quedó cubierta por dos vías.** Correo y pregunta secreta se entregaron en la misma semana y sin depender del administrador.
- **El panel se acercó al maquetado y se corrigió el propio Figma.** T-25 a T-28 ajustaron estilos y pantallas, y T-28 evitó que se copiaran tarifas y datos equivocados del diseño.
- **El acta del Sprint 1 y la guía de conflictos quedaron publicadas.** Las lecciones del sprint anterior ya viven en el repositorio y no solo en la conversación.

### Salió mal

- **Se mergearon PRs sin aplicar la revisión.** Los PRs #62, #63, #64 y #65 entraron el 10 de noviembre y sus hallazgos se convirtieron en tareas del sprint siguiente (#87, #88 y #89). La revisión llegó después del merge.
- **La integración volvió a concentrarse al final.** Nueve PRs se fusionaron entre el 8 y el 10 de noviembre (#55, #56, #57, #60, #61, #62, #63, #64 y #65), cuando la iteración terminaba el 8. La acción del Sprint 1 de integrar por tarea no se sostuvo.
- **El tablero cerró tareas después de la fecha del sprint.** Seis tareas se terminaron el 10 de noviembre y la iteración había terminado el 8; el tablero las conservó en el Sprint 2, así que el cierre real no coincide con la fecha del acta.
- **El vínculo PR ↔ issue del tablero quedó incompleto o duplicado.** #42 la cierran dos PRs distintos, #57 y #58, y el tablero muestra #57 aunque los estilos son de #58; #50 no tiene PR vinculado porque #66 no llevaba `Closes #50`; y #45 no tuvo PR porque se resolvió en Figma y se cerró con capturas.
- **Una dependencia vulnerable se detectó hasta la instalación.** El CVE-2026-45070 en `symfony/mime` lo encontró `composer audit` de forma manual y no hay esa verificación en el CI.
- **La deuda de la revisión del Sprint 1 se cerró tarde.** #46 y #47, generadas por la revisión de T-09 y T-14, no se terminaron hasta el 10 de noviembre, ya dentro del sprint siguiente.

### Qué cambiamos

1. **No mergear hasta que la revisión esté aplicada.** El autor espera el approve del revisor; si hay comentarios o `Request changes`, los hallazgos se corrigen en la misma rama y en el mismo PR antes de fusionar.
2. **Un PR, una tarea.** Separar alcances evita PRs como #57 y #58 cerrando la misma tarea y facilita revisar sin mezclar cambios.
3. **Cerrar el sprint con el tablero al día.** El último día se revisa la columna Done; lo que no esté terminado se mueve a la siguiente iteración en lugar de cerrarlo después con la iteración vencida.
4. **Verificar el vínculo del PR al abrirlo.** `Closes #N` exacto y sin duplicados; el campo "Linked pull requests" debe mostrar el issue correcto antes de pedir revisión.
5. **Correr `composer audit` y `pnpm audit` en el CI.** Así una dependencia vulnerable bloquea el merge en vez de aparecer al instalar en una máquina.
6. **Contrastar Figma con `docs/empresa.md` antes de implementar.** Si el diseño trae datos o tarifas que no coinciden, se corrige el diseño primero, como se hizo en T-28.

## Defectos

| Bug                                                                  | Severidad | Cómo se detectó              | Causa                      | PR  | Test que lo cubre                           |
| -------------------------------------------------------------------- | --------- | ---------------------------- | -------------------------- | --- | ------------------------------------------- |
| CVE-2026-45070 en `symfony/mime` (inyección de cabeceras en correos) | Media     | `composer audit` al instalar | Dependencia desactualizada | #54 | Ninguno: `composer audit` no corre en el CI |

Los demás bugs del periodo son del sprint siguiente: #46 y #47 vienen de la revisión del Sprint 1 y se cerraron el 10 de noviembre; #90 (errores de validación en usuarios) y los hallazgos #87 a #89 de los PRs #62 a #65 se atienden en el Sprint 3.

## Riesgos

| Riesgo                                                               | Probabilidad | Impacto | Mitigación                                                                                                             | Estado en el sprint                                                              |
| -------------------------------------------------------------------- | ------------ | ------- | ---------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------- |
| PRs mergeados sin aplicar la revisión                                | Alta         | Medio   | Publicar la revisión antes del merge; el autor no fusiona sin approve y aplica los hallazgos en la misma rama          | Ocurrió: #62 a #65; los hallazgos quedaron como #87 a #89 en el sprint siguiente |
| La integración se concentra al final del sprint                      | Alta         | Alto    | PRs de máximo dos o tres días, revisión diaria del tablero y cierre de PRs el jueves anterior al final de la iteración | Ocurrió: nueve PRs entre el 8 y el 10 de noviembre                               |
| El tablero no refleja el estado real (PRs sin vincular o duplicados) | Alta         | Bajo    | Un solo PR por tarea; revisar "Linked pull requests" al abrirlo y verificar el vínculo antes de pedir revisión         | Ocurrió: #42 con dos PRs que la cierran, #50 sin PR y #45 sin PR                 |
| Tareas que se cierran después de la fecha de la iteración            | Media        | Medio   | Revisar el tablero el último día y mover a la siguiente iteración lo que no esté terminado                             | Ocurrió: seis tareas cerradas el 10 de noviembre con la iteración vencida el 8   |
| Una dependencia vulnerable entra sin que el CI la detecte            | Media        | Alto    | `composer audit` y `pnpm audit` en el CI; revisar Dependabot al planear                                                | Ocurrió: CVE-2026-45070, detectado solo al instalar                              |
| La deuda de revisión del sprint anterior se arrastra                 | Media        | Medio   | Cerrar los hallazgos del sprint anterior antes de empezar tareas nuevas, o planearlos con puntos en el sprint          | Ocurrió: #46 y #47 se cerraron el 10 de noviembre                                |
| El diseño de Figma trae datos o tarifas equivocadas                  | Media        | Medio   | Contrastar Figma con `docs/empresa.md` antes de implementar y corregir el diseño primero                               | Ocurrió: T-28 corrigió tarifas, botones y datos inventados                       |
