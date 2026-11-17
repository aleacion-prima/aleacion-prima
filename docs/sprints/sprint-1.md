# Sprint 1 — Catálogos, inventario y despliegue

- **Sprint:** 1 (octubre de 2026)
- **Objetivo:** dejar en `main` la autenticación con roles, los catálogos, el inventario de insumos y el despliegue de prueba funcionando.
- **Equipo:** Humberto Castro Cortes (Product Owner), Cesar Leonel Morillon González (Scrum Master), Julio Alberto López Castro y Paloma Johana Portillo Hernández.
- **Compromiso:** 13 tareas, 41 puntos.

> Esta acta es también la **plantilla de los siguientes sprints**: al cerrar uno, copia este archivo a `sprint-2.md` (o el número que toque), cambia fechas y contenido, conserva las secciones y enlaza el archivo nuevo desde [`docs/README.md`](../README.md).

## Resumen

### Planeado y terminado

| Tarea                                    | Issue | PR  | Puntos | Responsable | Terminada  |
| ---------------------------------------- | ----- | --- | ------ | ----------- | ---------- |
| T0.2 Contrato de API                     | #2    | #25 | 2      | Humberto    | 2026-10-26 |
| T0.6 Bases de integración (Neon y Atlas) | #3    | #26 | 5      | Julio       | 2026-10-28 |
| T-06 Migraciones del modelo de datos     | #6    | #7  | 2      | Cesar       | 2026-10-13 |
| T-07 Inicio y cierre de sesión           | #8    | #20 | 3      | Cesar       | 2026-10-22 |
| T-08 Control de acceso por rol           | #9    | #23 | 3      | Cesar       | 2026-10-27 |
| T-09 Usuarios, roles y áreas             | #10   | #29 | 3      | Humberto    | 2026-10-29 |
| T-10 Catálogo de áreas y tarifas         | #11   | #32 | 2      | Paloma      | 2026-10-29 |
| T-11 Catálogo de productos               | #12   | #33 | 5      | Paloma      | 2026-10-29 |
| T-12 Catálogo de insumos                 | #13   | #34 | 2      | Paloma      | 2026-10-30 |
| T-13 Entradas de insumos y existencias   | #14   | #30 | 5      | Humberto    | 2026-10-29 |
| T-14 Ajustes por auditoría               | #15   | #31 | 2      | Humberto    | 2026-10-29 |
| T-15 Detección de stock mínimo           | #16   | #27 | 2      | Julio       | 2026-10-28 |
| T-16 Despliegue de prueba                | #17   | #28 | 5      | Cesar       | 2026-10-30 |

### Agregado durante el sprint

Ninguna tarea fuera de la planeación. Las tareas T0.5 (wireframes, #4), #18 (guía visual) y T-17 (base de componentes, #21) se cerraron en octubre, pero el tablero no las asigna a un sprint.

### Resultado

Se cerraron **las 13 tareas comprometidas: 41 de 41 puntos**. No quedó deuda del Sprint 1.

El trabajo dejó en `main` la base de autenticación y permisos, los catálogos de áreas, productos e insumos con su API y sus pantallas, el inventario de insumos con entradas, ajustes y detección de stock mínimo, y el despliegue de prueba sobre Azure.

## Retrospectiva

### Salió bien

- **Se cumplió el compromiso completo.** 13 de 13 tareas y 41 de 41 puntos, sin arrastrar deuda al siguiente sprint.
- **La base del sistema quedó completa.** Autenticación con roles, catálogos, inventario y despliegue conviven en `main` con API, panel y pruebas. Las tareas que tocaban API y panel se entregaron juntas en el mismo PR.
- **El cambio de proveedor de hosting no detuvo el despliegue.** DigitalOcean salió del GitHub Student Developer Pack, y en lugar de improvisar se evaluaron alternativas y se documentó la decisión en [ADR-0015](../decisiones/0015-servidor-en-azure-for-students.md).
- **La revisión de PR encontró errores antes de fusionar.** Se corrigieron permisos, validaciones y respuestas de la API con sus pruebas antes del merge.
- **El CI y los hooks sostuvieron el formato.** `pnpm check` como puerta única evitó que llegaran a `main` fallos de lint, tipos o pruebas.
- **La documentación de decisiones se mantuvo al día:** [ADR-0014](../decisiones/0014-github-flow-squash.md), [ADR-0015](../decisiones/0015-servidor-en-azure-for-students.md), el [contrato de la API](../api.md) y el [modelo de datos](../modelo-datos.md).

### Salió mal

- **La integración se concentró al final.** Cinco PRs (T-09, T-10, T-11, T-13 y T-14) se fusionaron el 29 de octubre, un día antes del cierre. Eso produjo revisiones simultáneas y conflictos que se pudieron evitar.
- **Conflictos al fusionar ramas en paralelo.** Varias ramas agregaban rutas en los mismos archivos (`apps/api/routes/api.php` y `apps/panel/src/router/routes.ts`), y resolverlos costó tiempo y cuidado.
- **Issues que no se cerraban solos.** Varios PRs no tenían `Closes #<número>` en el formato que GitHub reconoce, así que hubo que cerrar los issues a mano.
- **El tablero no siempre reflejó el PR.** T-11 quedó sin PR vinculado en el tablero aunque entró con el #33; sin ese vínculo es más difícil reconstruir qué se revisó.
- **Los accesos al repositorio tardaron en configurarse.** La invitación a la organización y las llaves SSH no estuvieron listas para todos desde el primer día, y eso retrasó los primeros pushes.
- **El proveedor de hosting planeado dejó de estar disponible.** DigitalOcean salió del Student Developer Pack y T-16 se tuvo que replantear sobre Azure for Students.

### Qué cambiamos

1. **Integrar por tarea, no al final.** Ningún PR abierto más de tres días; si una tarea no cabe, se parte en dos. El tablero se revisa a diario y los PRs se fusionan en cuanto están aprobados.
2. **Actualizar la rama antes de pedir revisión.** `git merge origin/main` (o `git rebase origin/main`) en la rama propia antes de marcar el PR como listo.
3. **Resolver conflictos de rutas con la guía y con la otra persona.** Nunca se elige un lado a ciegas; se integran ambas rutas y se comprueba con `php artisan route:list` o `pnpm typecheck`. La guía está en el [README](../../README.md#resolver-conflictos).
4. **Escribir `Closes #N` exacto y verificarlo.** Sin backticks, sin espacios y en la descripción del PR; al abrirlo, GitHub debe mostrar el issue vinculado.
5. **Vincular el PR en el tablero al abrirlo.** Así el sprint se puede reconstruir sin buscar en el historial de Git.
6. **Onboarding el día 1.** Invitación a la organización, llave SSH, `ssh -T git@github.com` y `pnpm install` (que activa los hooks) antes de asignar tareas que requieran push.
7. **Reservar el último día para cerrar el sprint.** El último día es para la retro, el acta y la planeación del siguiente; no para integrar código.
8. **Revisar la vigencia de los planes gratuitos al planear.** Si el sprint depende de un crédito o plan gratuito, se confirma al inicio y se documenta un plan B.

## Riesgos

| Riesgo                                                    | Probabilidad | Impacto | Mitigación                                                                                                                                            | Estado en el sprint                                                    |
| --------------------------------------------------------- | ------------ | ------- | ----------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------- |
| El proveedor de hosting planeado deja de estar disponible | Media        | Alto    | Confirmar al planear que el plan y los créditos sigan vigentes; tener un plan B sin tarjeta y documentarlo en un ADR                                  | Ocurrió: DigitalOcean salió del Pack; se resolvió con Azure (ADR-0015) |
| Los accesos al repositorio tardan en configurarse         | Media        | Medio   | Checklist de onboarding el día 1 (organización, SSH, `ssh -T`, hooks); no asignar tareas que requieran push hasta confirmarlo                         | Ocurrió: retrasó los primeros pushes                                   |
| La integración se concentra al final del sprint           | Alta         | Alto    | PRs de máximo dos o tres días, revisión diaria del tablero y fecha límite de PRs el jueves anterior al cierre; el último día se reserva para la retro | Ocurrió: cinco PRs se fusionaron el 29 de octubre                      |
| Conflictos al fusionar ramas en paralelo                  | Alta         | Medio   | Actualizar la rama con `main` antes de pedir revisión; PRs pequeños; resolver rutas con la guía del README y sin descartar el lado ajeno              | Ocurrió en `routes/api.php` y `routes.ts`                              |
| Issues que no se cierran solos por el formato de `Closes` | Alta         | Bajo    | `Closes #N` exacto en la descripción; verificar el issue vinculado al abrir el PR                                                                     | Ocurrió: se cerraron a mano                                            |
| La VM de 1 GiB se queda sin memoria                       | Media        | Medio   | Swap de 2 GiB, imágenes construidas en CI y no en la VM; vigilar `docker stats` y el consumo en el portal de Azure                                    | Sin incidentes en el sprint                                            |

## Plantilla para el siguiente sprint

Para abrir el acta del Sprint 2:

1. Copia este archivo a `docs/sprints/sprint-2.md`.
2. Actualiza sprint, fechas, objetivo, equipo y compromiso (tareas y puntos).
3. Llena las tablas con los datos del tablero, enlazando `#issue` y `#PR`, y anotando puntos y responsable.
4. Recorre la retro con el equipo: qué salió bien, qué salió mal y qué cambiamos. Cada cambio debe ser una acción concreta, no un deseo.
5. Revisa la tabla de riesgos al inicio y al cierre: agrega los que aparecieron, actualiza el estado de los que ya estaban y quita los que dejaron de aplicar.
6. Enlaza el archivo nuevo desde [`docs/README.md`](../README.md) y ciérralo con un PR `docs:`.

Esqueleto:

```text
# Sprint N — <objetivo en una línea>

- Sprint:
- Fechas:
- Objetivo:
- Equipo:
- Compromiso: <n> tareas, <n> puntos

## Resumen
### Planeado y terminado
| Tarea | Issue | PR | Puntos | Responsable | Terminada |
### Agregado durante el sprint
### Resultado

## Retrospectiva
### Salió bien
### Salió mal
### Qué cambiamos

## Riesgos
| Riesgo | Probabilidad | Impacto | Mitigación | Estado |
```
