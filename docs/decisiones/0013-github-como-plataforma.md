# ADR-0013: GitHub como plataforma del repositorio, la integración continua, las imágenes y el tablero

- **Estado:** aceptada
- **Alcance:** `infra`, `docs`

## Contexto

El proyecto necesita, además de alojar el código, cuatro cosas: integración y despliegue continuos (RNF-15 exige que nada entre a `main` sin revisión ni pruebas), un registro de imágenes Docker para el despliegue por etiqueta (ADR-0007), un tablero para llevar Scrum junto al código (sección 6 del anteproyecto) y avisos de dependencias vulnerables. El equipo son cuatro personas sin presupuesto; el servidor y el dominio salen de los créditos del GitHub Student Developer Pack. El anteproyecto ya compromete Git y GitHub en la sección 7.

## Decisión

1. **El repositorio vive en GitHub**, en la organización `aleacion-prima`, con los cuatro integrantes como miembros. Git es el control de versiones; no hubo alternativa real que evaluar.
2. **GitHub Actions** corre la integración continua (`ci.yml`: formato, análisis estático, tipos y pruebas) y el despliegue (`deploy.yml`).
3. **GitHub Container Registry (GHCR)** guarda las imágenes etiquetadas de la API y de Caddy; el servidor las descarga de ahí.
4. **GitHub Projects** es el tablero del equipo: issues con plantillas y etiquetas, milestones por hito y campos de sprint, puntos y prioridad.
5. **Dependabot** avisa de dependencias vulnerables. Las credenciales de producción viven en Secrets y Variables de Actions.
6. **El repositorio es público.** El profesor y cualquier evaluador lo ven sin invitación; por eso ningún `.env`, credencial ni dato real entra nunca al repositorio (los datos de demostración son ficticios, RNF-16).

## Consecuencias

- Todo depende de un solo proveedor. Es aceptable: el código es Git y se mueve a cualquier lado; lo que se perdería es la configuración de Actions, Projects y GHCR, que es pequeña.
- Issue Types y algunas funciones de Projects solo existen en organizaciones, por eso el repositorio está en una y no en una cuenta personal.
- Los minutos de Actions y el almacenamiento de GHCR son gratuitos en repositorios públicos.
- Las imágenes publicadas en GHCR también son públicas: el archivo de entorno del servidor lo genera el pipeline desde un secret y nunca va dentro de la imagen.
- Cada integrante necesita cuenta de GitHub y llave SSH propia; no se comparten cuentas.
- Las reglas de la rama `main` (PR obligatorio, una aprobación, CI en verde, Squash and merge, sin push forzado) se configuran en GitHub y se documentan en el README, no en el código.

## Alternativas descartadas

- **GitLab:** CI, registro de imágenes y tablero equivalentes, pero los créditos del servidor, el dominio y la organización del equipo ya están en GitHub; mover solo el código no aporta nada y duplica cuentas.
- **Bitbucket:** sin registro de contenedores propio, así que el despliegue por imagen etiquetada necesitaría un servicio adicional.
- **Un servidor Git propio (Gitea, Forgejo):** mantener un servidor más para un proyecto de un cuatrimestre, sin tablero ni registro integrados.
