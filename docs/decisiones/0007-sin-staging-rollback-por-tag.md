# ADR-0007: Sin ambiente de staging; CI obligatorio y rollback por tag de imagen

- **Estado:** aceptada
- **Alcance:** `infra`

## Contexto

El anteproyecto original proponía un ambiente de staging como respuesta al riesgo RE-07 (caída del servidor o error en el despliegue). Hay un solo droplet pagado con créditos del GitHub Student Pack y un equipo de cuatro personas con tiempo limitado: mantener dos servidores, dos dominios y dos juegos de credenciales duplicaría el trabajo de infraestructura para un proyecto de una materia.

## Decisión

1. **Dos ambientes: desarrollo local y producción.** No hay staging. Las bases de "integración" (Neon/Atlas) sirven para pruebas conjuntas contra datos compartidos, no como un servidor aparte.
2. **El CI es el filtro.** `main` está protegida: nada se fusiona sin que `ci.yml` pase (lint, typecheck, Vitest, Pest, Larastan; E2E cuando existan) y sin una revisión.
3. **Cada despliegue es una imagen etiquetada** en GHCR (`ghcr.io/<owner>/aleacion-prima-{api,caddy}:<TAG>`), construida por `deploy.yml` a partir de `main`. En el servidor, `compose.prod.yaml` lee `TAG` de un `.env`.
4. **Rollback = cambiar `TAG` a la imagen anterior y `docker compose up -d`.** Menos de un minuto, sin reconstruir nada.
5. **Respaldos:** restauración a un punto en el tiempo de Neon (incluida en el plan) y `mongodump` programado desde el servidor. **Monitoreo básico:** healthchecks de Compose y alertas de disponibilidad del dominio.

## Consecuencias

- Como no hay staging, **las migraciones deben ser compatibles hacia atrás** con la versión anterior (agregar columnas antes de usarlas, no renombrar en el mismo despliegue). Si una migración no lo es, el rollback también exige restaurar la BD, y eso se avisa en el PR.
- El CI tiene que ser confiable: un test "flaky" que se ignore es un bug que llega a producción. Los E2E se agregan al CI en cuanto existan flujos reales.
- `api.env` de producción lo genera el pipeline desde el secret `API_ENV`; nadie lo edita a mano en el servidor.
- El volumen `caddy_data` guarda los certificados TLS y **no se borra**; borrarlo obliga a reemitirlos y Let's Encrypt tiene límites de emisión.
- Todo lo escrito para producción (`compose.prod.yaml`, Caddyfile, `deploy.yml`) queda **sin probar** hasta tener droplet y dominio; el primer despliegue se hace con calma y con el equipo presente.

## Alternativas descartadas

- **Staging en un segundo droplet**: costo en créditos, dominio extra, credenciales extra y despliegues dobles. Para cuatro personas y un semestre, el CI más el rollback cubren el mismo riesgo a una fracción del esfuerzo.
- **Desplegar `main` directamente sin etiquetar imágenes** (`latest`): no habría a qué volver cuando algo falle.
- **Staging en el mismo droplet** (otro Compose en otro puerto): comparte recursos y bases con producción, así que no aísla nada y sí complica Caddy.
