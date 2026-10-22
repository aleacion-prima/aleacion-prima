# AGENTS.md

Instrucciones para asistentes de IA que trabajen en este repositorio.
Para el contexto completo (objetivo, decisiones y su porqué, estado actual), lee **`docs/`** antes de hacer cambios grandes.

## Proyecto

Sistema de trazabilidad de lotes de producción y ventas para una empresa metalúrgica ("Aleación Prima"). Monorepo con pnpm workspaces:

| Carpeta           | Qué es                                                             | Stack                                                                                                       |
| ----------------- | ------------------------------------------------------------------ | ----------------------------------------------------------------------------------------------------------- |
| `apps/api`        | API REST + WebSockets                                              | Laravel (PHP 8.4), Sanctum, Reverb, Spatie Permission, Scramble, PostgreSQL + MongoDB                       |
| `apps/panel`      | Panel administrativo **y** app Android                             | Quasar `@quasar/app-vite` v3 (Vue 3 + TS), Pinia, Vue Router, VueUse, Laravel Echo, Capacitor, `qrcode.vue` |
| `apps/web`        | Sitio público (catálogo sin precios)                               | Astro, estático, islas de Vue, Laravel Echo                                                                 |
| `packages/types`  | Tipos TS compartidos de la API (`@aleacion-prima/types`)           | TypeScript, sin build                                                                                       |
| `packages/config` | ESLint, Prettier y tsconfig compartidos (`@aleacion-prima/config`) | TypeScript                                                                                                  |
| `e2e`             | Pruebas de extremo a extremo                                       | Playwright                                                                                                  |
| `infra`           | Dockerfiles y Caddyfile                                            | Docker, Caddy, FrankenPHP                                                                                   |
| `scripts`         | Scripts del repo (p. ej. `setup.ts`, `labels.ts`)                  | TypeScript ejecutado directo con Node                                                                       |

`apps/api` y `apps/web` tienen su propio `AGENTS.md`/`CLAUDE.md` con reglas específicas del framework; aplican además de este. **Si alguna regla de esos archivos contradice a este, este tiene prioridad.**

### Entornos de base de datos

| Entorno      | PostgreSQL                       | MongoDB                         |
| ------------ | -------------------------------- | ------------------------------- |
| Desarrollo   | Docker local (`postgres`, v18)   | Docker local (`mongo`, v7)      |
| Integración  | Neon (proyecto "Aleacion Prima") | MongoDB Atlas                   |
| Producción   | Neon (instancia distinta)        | MongoDB Atlas                   |
| Tests (Pest) | SQLite en memoria                | Ninguno (debe fallar si se usa) |

---

## 🚫 Reglas obligatorias

### 1. Git: prohibido

**No ejecutes ningún comando que modifique el estado de Git.** Esto incluye, sin excepción:

`git add`, `commit`, `push`, `pull`, `fetch`, `merge`, `rebase`, `cherry-pick`, `checkout`, `switch`, `branch`, `tag`, `reset`, `restore`, `revert`, `stash`, `clean`, `rm`, `mv`, `remote`, `init`, `config`, `submodule`, y cualquier uso de `--no-verify`.

- No edites nada dentro de `.git/` ni los hooks de `.husky/`.
- No uses herramientas o integraciones que hagan commits, PRs o pushes por su cuenta.
- Solo puedes **leer** el estado con `git status`, `git diff` y `git log`.
- Cuando termines un cambio, **propón** un mensaje de commit en formato Conventional Commits (ver abajo) y deja que la persona lo haga.
- Si propones el texto de un PR, sigue `.github/pull_request_template.md`: título en Conventional Commits y `Closes #<issue>` en la descripción.

### 2. Bases de datos

- **Migraciones y seeders solo contra la BD local de Docker**, es decir, cuando `apps/api/.env` tenga `DB_HOST=postgres`. **Nunca** contra Neon ni Atlas (integración o producción).
- Si el `DB_HOST` es otro, no corras comandos que escriban en la BD; avisa a la persona.
- **Los tests nunca tocan una BD real.** `apps/api/phpunit.xml` usa SQLite en memoria y una `MONGODB_URI` inválida a propósito, para que un test que intente usar MongoDB falle en vez de escribir en Atlas. No cambies eso. Si un test necesita MongoDB, usa mocks o fakes.

### 3. Otras reglas

- **No agregues, quites ni actualices dependencias** (pnpm o Composer) sin que la persona lo pida o lo apruebe primero. Al instalar, versión fija: `pnpm --filter <app> add <paquete>@<versión>`.
- **No toques archivos de entorno ni secretos**: `.env`, `api.env`, `apps/api/.env`. Si falta una variable, dilo y actualiza solo el `.env.example` correspondiente (sin valores secretos).
- **TypeScript 6, no 7.** `astro check` no soporta TS 7.
- **No uses Axios.** El cliente HTTP es propio, sobre `fetch` con VueUse.
- **No uses JWT.** La autenticación es con Sanctum (ver Autenticación).
- **No desactives reglas de ESLint, Larastan o TypeScript** (`eslint-disable`, `@ts-ignore`, `any`, bajar el nivel de PHPStan) para que algo pase. Corrige la causa; si de verdad no hay otra opción, explica por qué en el comentario.
- **No modifiques archivos generados**: `.quasar/`, `.astro/`, `dist/`, `vendor/`, `node_modules/`, `pnpm-lock.yaml` a mano.
- Mantente dentro del alcance de lo que se pidió. Si ves algo más que convendría cambiar, menciónalo en lugar de hacerlo.

---

## Comandos

**Todo se ejecuta desde la raíz del repo.** Para un solo proyecto usa `--filter` con el `name` de su `package.json` (no el nombre de la carpeta): `panel`, `web`, `e2e`, `@aleacion-prima/types`, `@aleacion-prima/config`.

### Primera vez

```bash
pnpm install          # dependencias de Node de todo el workspace
pnpm setup            # .env, llaves, imagen de la API, composer install, BD locales y migraciones
```

`pnpm setup` se puede correr las veces que sea necesario: se salta lo que ya existe.

### Día a día

```bash
pnpm api:up                           # API :8000, Reverb :8080, cola, Postgres y Mongo
pnpm api:down                         # detener todo
pnpm api:logs                         # ver logs
pnpm artisan <comando>                # php artisan dentro del contenedor
pnpm composer <comando>               # composer dentro del contenedor
pnpm --filter panel dev               # panel en http://localhost:9000
pnpm --filter web dev                 # sitio en http://localhost:4321
```

- Documentación de la API (Scramble): `http://localhost:8000/docs/api` (solo en entorno `local`).
- `pnpm artisan` y `pnpm composer` usan `docker compose run --rm api`, que solo levanta lo que esté en `depends_on`. Si algo "no resuelve el host" (`postgres`, `mongo`, `reverb`), revisa `docker compose ps` y corre `pnpm api:up`.

### Calidad y pruebas

```bash
pnpm lint                             # ESLint
pnpm format                           # Prettier (escribir)
pnpm typecheck                        # tsc / vue-tsc / astro check
pnpm test                             # Vitest (panel)
pnpm test:api                         # Pest
pnpm analyse:api                      # Larastan
pnpm test:e2e                         # Playwright (requiere la app levantada)
pnpm check                            # todo lo anterior salvo E2E (= CI)
```

- En zsh, los argumentos con `*` van entre comillas simples: `'@aleacion-prima/config@workspace:*'`.

### Antes de dar un cambio por terminado

Corre lo que aplique a lo que tocaste y asegúrate de que pase:

- Frontend, paquetes o scripts: `pnpm lint`, `pnpm typecheck`, `pnpm test`
- Backend: `pnpm test:api`, `pnpm analyse:api`
- Formato: `pnpm format`

Si algo falla y no es por tu cambio, repórtalo en lugar de "arreglarlo" por tu cuenta.

---

## Autenticación y permisos

- **Panel:** cookie de sesión de Sanctum (`HttpOnly`) + CSRF. Antes del login se llama a `/sanctum/csrf-cookie` (`prepararCsrf()` en `apps/panel/src/services/api.ts`).
- **App móvil:** tokens personales de Sanctum con abilities de solo lectura (p. ej. `createToken('movil', ['lotes:read'])`), enviados como `Authorization: Bearer`.
- Ambos entran por `auth:sanctum` y resuelven el mismo `User`. **Sin JWT.**
- **Roles con Spatie Permission, un solo guard: `web`.** No crees roles con guard `sanctum`. Roles definidos en el enum `App\Enums\Rol` (`administrador`, `ventas`, `encargado`, `cliente`) y sembrados por `RolesSeeder`. En rutas: `->middleware(['auth:sanctum', 'role:administrador|ventas'])`.
- **Roles ≠ abilities del token.** El rol dice qué puede hacer la persona; la ability limita qué puede hacer ese token. La app móvil debe pasar ambas validaciones.
- Por ahora solo hay roles; los permisos finos se agregan con cada historia de usuario.

---

## Convenciones

### Generales

- **Todo el código de frontend, paquetes, scripts y configuración va en TypeScript.**
- Los términos del dominio van en **español**, igual que en `packages/types`: `Lote`, `EstadoLote`, `merma`, `area`, `pedido`, `imprevisto`.
- **Identificadores sin acentos ni ñ** (`area`, `anio`, `descripcion`); los acentos solo van en textos visibles y comentarios. Identificadores en `camelCase` (TS) o según la convención de Laravel (PHP).
- Comentarios en español, solo donde expliquen el _porqué_.
- Todos los archivos usan saltos de línea **LF** (lo fuerza `.gitattributes`). No introduzcas CRLF.

### TypeScript

- Modo estricto con `noUncheckedIndexedAccess`: maneja los `undefined` al indexar arreglos y objetos.
- Importa tipos con `import type` (el proyecto usa `verbatimModuleSyntax`).
- Los tipos que representan datos de la API van en `packages/types`, no duplicados en cada app. Solo tipos, sin constantes. Las fechas llegan como `string` ISO 8601.
- En TypeScript 6, `types` vale `[]` por defecto: si un archivo usa APIs de Node, su `tsconfig` necesita `"types": ["node"]` y el archivo debe estar en su `include`.
- **Scripts en `scripts/`:** Node los ejecuta directo quitando los tipos, así que solo se permite sintaxis que se pueda borrar sin transformar: nada de `enum`, `namespace` ni propiedades en el constructor (`constructor(private x: string)`).

### Vue / Quasar (`apps/panel`)

- El panel usa **`@quasar/app-vite` v3**: la configuración se importa desde `#q-app` y las variables de entorno se leen con `import.meta.env` (no `process.env`).
- Variables públicas (`PUBLIC_API_URL`, `PUBLIC_REVERB_*`) se definen en `build.define` de `quasar.config.ts` y se tipan en `src/env.d.ts` (`interface ImportMetaEnv`). Llegan como `string`; convierte el puerto con `Number()`.
- Componentes con `<script setup lang="ts">` y Composition API.
- Usa los componentes de Quasar antes de crear los propios.
- Estado global en Pinia; peticiones HTTP con el cliente propio basado en VueUse (`createFetch`, en `src/services/api.ts`).
- Tiempo real con Laravel Echo + Reverb (`src/services/echo.ts`): `obtenerEcho()` es perezoso (no antes del login) y `desconectarEcho()` se llama al cerrar sesión. Los transportes se limitan según `PUBLIC_REVERB_SCHEME` (`ws` en http, `wss` en https).
- Tests junto al componente: `NombreComponente.spec.ts`, montando con `global: { plugins: [Quasar] }`.
- Carpetas por dominio, no por tipo: `components/lotes`, `components/comunes`, `components/navegacion`; `pages/acceso`, `pages/tablero`, `pages/lotes`, `pages/sistema`. Los catálogos de etiquetas, colores e iconos que solo usa la interfaz van en `src/catalogos`. Nombres de componente compuestos (`ChipEstado`, no `Chip`), por la regla `vue/multi-word-component-names`.

### Etiquetas y QR de los lotes

- **El QR se genera en el panel**, con `qrcode.vue` (componente `QrcodeSvg`, salida SVG, nivel de corrección `Q`, margen `4`). No hay endpoint ni paquete PHP para esto.
- **El QR contiene únicamente el código del lote** (p. ej. `LOT-2026-0042`), sin URL ni otros datos. No se guarda la imagen en la BD.
- La etiqueta se imprime desde el navegador (`@media print`, `break-inside: avoid`).

### App móvil (mismo proyecto que el panel)

- La app Android es **de solo lectura**: escanear el QR de un lote y ver su estado, área actual e historial. **Nunca** agregues captura ni edición en el modo móvil.
- Las rutas y el código exclusivo de un modo se separan con `import.meta.env.QUASAR_MODE === 'capacitor'` (o el booleano `import.meta.env.QUASAR_CAPACITOR_MODE`). Quasar evalúa esos `if` en compilación y elimina las ramas falsas, así el código del panel administrativo no se incluye en el APK.
- En Android, `localhost` es el propio celular: usa `10.0.2.2` en el emulador o la IP de la máquina en un dispositivo físico.
- El "solo lectura" también se valida en el backend (tokens de Sanctum con abilities de lectura). Ocultar botones en la UI no es suficiente.

### Astro (`apps/web`)

- Sitio estático. Los datos que cambian (disponibilidad) se piden a la API desde islas de Vue.
- **Nunca muestres precios** en el sitio público.
- Las islas que usan el navegador llevan directiva `client:*` (`client:load` o `client:visible`); sin ella, `onMounted` nunca se ejecuta.
- Echo es **perezoso** (`obtenerEcho()` en `src/lib/echo.ts`): nunca crees `new Echo` en el nivel superior de un módulo, porque se ejecutaría en el build estático, donde no existe `window`. Sin `authorizer` ni cookies: solo canales públicos.
- Suscríbete en `onMounted` y haz `stopListening` en `onUnmounted`.
- Los eventos de canales públicos **solo transmiten lo indispensable** con `broadcastWith()` (p. ej. `id` y `disponible`), nunca el modelo completo y **nunca precios**.

### Laravel (`apps/api`)

- Sigue las convenciones de Laravel. Formato con Pint; análisis con Larastan (nivel 5).
- Usa **`Model::query()->...`** en vez de llamadas estáticas mágicas (`Role::pluck()`, `Role::count()`): el editor y Larastan infieren mejor los tipos.
- **Form Requests + API Resources en todos los endpoints**: así Scramble documenta la API casi sola.
- Tests con Pest. Usa factories y `RefreshDatabase` (corre sobre SQLite en memoria). Usa las funciones de `Pest\Laravel` (`use function Pest\Laravel\seed;`) en lugar de `$this->seed()`.
- Datos transaccionales (lotes, inventario, costos, ventas) en PostgreSQL; bitácora e imprevistos no estructurados en MongoDB.
- Modelos de MongoDB: extienden `MongoDB\Laravel\Eloquent\Model` con `protected $connection = 'mongodb';`. La conexión por defecto sigue siendo `pgsql`.
- Eventos de broadcast: nombre con `broadcastAs()` (p. ej. `'disponibilidad.actualizada'`; en el cliente se escucha con punto inicial: `.disponibilidad.actualizada`) y carga mínima con `broadcastWith()`.
- Los datos de prueba compartidos por el equipo se definen en seeders, no a mano en la BD.

### Docker

- `compose.yaml` es solo para desarrollo; `compose.prod.yaml` para producción.
- En `compose.yaml`, los servicios heredan de `x-api` con `<<: *api`. **Las listas no se combinan**: si un servicio define su propio `volumes:`, debe repetir `./apps/api:/app`.
- El Dockerfile de la API tiene etapas `base`, `dev` y `prod`. Las variables y pasos de producción (`APP_ENV=production`, copiar el código, `composer install --no-dev`) van **solo** en `prod`.
- El contexto de build de la API es `apps/api`, así que el `.dockerignore` que aplica es `apps/api/.dockerignore`, no el de la raíz.

### Commits (para proponer, no para ejecutar)

Conventional Commits, en español:

```
<tipo>(<alcance>): <descripción en minúsculas>

feat(panel): agregar tablero de lotes en tiempo real
fix(api): corregir cálculo de merma por área
chore: actualizar configuración de prettier
```

- Tipos: `feat`, `fix`, `docs`, `style`, `refactor`, `perf`, `test`, `build`, `ci`, `chore`, `revert`.
- Alcances: `api`, `panel`, `web`, `types`, `config`, `infra`, `e2e`, `docs`, `scripts`. Omítelo si el cambio abarca varios. (`movil` es un label de GitHub, no un alcance: el código vive en `panel`.)
