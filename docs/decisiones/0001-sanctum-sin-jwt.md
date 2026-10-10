# ADR-0001: Autenticación con Sanctum, sin JWT

- **Estado:** aceptada
- **Alcance:** `api`, `panel`

## Contexto

El sistema tiene dos clientes autenticados con necesidades distintas:

- El **panel web** (Quasar, SPA) lo usan administrador, ventas y encargados de área desde el navegador, en el mismo dominio que la API.
- La **app móvil** (Capacitor) la usan los encargados de área para escanear QR y consultar lotes. Es de solo lectura y corre fuera del navegador, con origen `https://localhost`.

Hacía falta un mecanismo que cubriera ambos casos sin mantener dos sistemas de autenticación, y que permitiera revocar el acceso de un dispositivo o persona al momento (por ejemplo, un celular extraviado o un empleado que se va). El anteproyecto ya justificaba Laravel Sanctum en la sección 7.

## Decisión

Usar **Laravel Sanctum** para los dos clientes:

| Cliente   | Mecanismo                                                     | Middleware     |
| --------- | ------------------------------------------------------------- | -------------- |
| Panel     | Cookie de sesión `HttpOnly` + token CSRF (`XSRF-TOKEN`)       | `auth:sanctum` |
| App móvil | Token personal con abilities de lectura (p. ej. `lotes:read`) | `auth:sanctum` |

Ambos resuelven el mismo `User` y el mismo guard `web`. Los tokens de la app son opacos, se guardan hasheados en la BD y se revocan borrando el registro.

**No se usa JWT** ni Laravel Passport.

## Consecuencias

- `bootstrap/app.php` lleva `$middleware->statefulApi()` para que las peticiones del panel se traten como sesión.
- CORS debe permitir credenciales (`supports_credentials: true`) y listar explícitamente los orígenes: panel, sitio público y `https://localhost` (Capacitor). Ver `docs/autenticacion.md` para las variables por entorno.
- El panel llama a `/sanctum/csrf-cookie` antes del login (`prepararCsrf()` en `apps/panel/src/services/api.ts`) y manda `X-XSRF-TOKEN` en cada petición.
- El cliente HTTP del panel necesitará una rama para el modo Capacitor que envíe `Authorization: Bearer <token>` en lugar de la cookie.
- Los tokens de la app no expiran por sí solos salvo que se configure `expiration` en `config/sanctum.php`; la revocación es manual o al cerrar sesión. Aceptable para un equipo pequeño de encargados.
- La autorización de canales privados de Reverb (`/broadcasting/auth`) pasa por el mismo `auth:sanctum`.

## Alternativas descartadas

- **JWT** (`tymon/jwt-auth` o similar): sin estado, por lo que revocar un token exige una lista negra que reintroduce el estado; obliga a manejar refresh tokens en los clientes; y no aporta nada frente a Sanctum en una API consumida solo por clientes propios. Además habría que mantener JWT para la app y sesión para el panel: dos mecanismos.
- **Laravel Passport**: implementa OAuth2 completo (clientes, scopes, flujos de autorización), pensado para terceros que consumen la API. Aquí no hay terceros; es complejidad sin beneficio.
- **Solo cookies para todo**: Capacitor no comparte cookies con el navegador de forma confiable y el origen `https://localhost` complica `SANCTUM_STATEFUL_DOMAINS`; los tokens son más simples para la app.
