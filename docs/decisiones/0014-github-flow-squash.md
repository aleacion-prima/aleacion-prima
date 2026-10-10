# ADR-0014: GitHub Flow con una rama por tarea y Squash and merge

- **Estado:** aceptada
- **Alcance:** `api`, `panel`, `web`, `types`, `config`, `infra`, `e2e`, `docs`, `scripts`

## Contexto

Cuatro personas trabajan en paralelo sobre un monorepo en sprints de dos semanas. No hay ambiente de staging ni versiones paralelas: cada fusión a `main` es candidata a despliegue (ADR-0007). RNF-15 exige que todo cambio entre por Pull Request con una revisión y la integración continua aprobada. Los mensajes de commit ya siguen Conventional Commits, verificados por commitlint en el hook `commit-msg`. Hacía falta un modelo de ramas que cupiera en eso y que no exigiera más Git del que el equipo domina.

## Decisión

1. **GitHub Flow.** Una sola rama permanente, `main`, siempre desplegable. Nadie hace push directo a `main` ni push forzado.
2. **Una rama corta por tarea**, nombrada `<tipo>/<descripcion-corta>` en minúsculas, sin acentos y con guiones; el tipo es el del commit principal de la tarea (`feat/tablero-lotes`, `fix/calculo-merma`, `docs/diccionario-datos`). Una rama corresponde a un issue del tablero.
3. **Commits en Conventional Commits, en español**, con los alcances fijados en `commitlint.config.ts` (`api`, `panel`, `web`, `types`, `config`, `infra`, `e2e`, `docs`, `scripts`).
4. **Toda rama entra por Pull Request** con el título también en Conventional Commits y `Closes #<número>` en la descripción. Para fusionar se requieren el CI en verde y la aprobación de otro integrante (ADR-0007, punto 2).
5. **Solo Squash and merge.** Cada tarea queda como un solo commit en `main`, cuyo mensaje es el título del PR. La rama se borra al fusionar.
6. Las correcciones urgentes siguen la misma vía: rama `fix/...`, PR, CI, revisión. No hay atajo.

## Consecuencias

- El historial de `main` es lineal y legible: un commit por tarea, con mensaje convencional. `git log --oneline` es el changelog, y revertir una tarea es revertir un commit.
- Los commits intermedios de cada rama desaparecen de `main` al fusionar. Es intencional: la rama es borrador; el PR es la unidad de trabajo.
- Como el título del PR es lo que queda, se revisa con el mismo cuidado que un commit. Conviene validar los títulos de PR en el CI más adelante.
- Las ramas deben ser cortas (días, no semanas): una tarea del tablero, no un sprint entero. Si una rama vive mucho, se parte.
- Sin ramas `develop` ni `release`, el despliegue de prueba y el final se hacen desde `main` con la etiqueta de imagen correspondiente (ADR-0007); no hay rama de versión que mantener.
- Los hooks de Husky (`pre-commit`, `commit-msg`, `pre-push`) hacen cumplir el formato y el mensaje en cada máquina; las reglas de la rama `main` lo hacen cumplir en GitHub aunque alguien se salte los hooks.

## Alternativas descartadas

- **Git Flow:** sus ramas `develop`, `release` y `hotfix` sirven para publicar versiones numeradas y mantener varias en paralelo; aquí hay una sola versión, sin staging, y cada fusión a `main` se despliega. Sería ceremonia sin uso.
- **Trunk-based con commits directos a `main`:** elimina el PR y con él la revisión y el CI previo, lo que contradice RNF-15.
- **Merge commits o Rebase and merge:** los merge commits llenan `main` de commits de trabajo y de fusión; el rebase conserva commits intermedios que nunca pasaron el CI por separado. El squash deja exactamente lo que se revisó.
