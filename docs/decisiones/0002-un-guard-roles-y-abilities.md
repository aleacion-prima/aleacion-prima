# ADR-0002: Un solo guard; roles y abilities son cosas distintas

- **Estado:** aceptada
- **Alcance:** `api`, `panel`, `types`

## Contexto

Hay cuatro tipos de usuario (administrador, ventas, encargado de área, cliente) y dos formas de entrar a la API (cookie de sesión desde el panel, token desde la app; ver ADR-0001). Spatie Permission asocia cada rol a un _guard_, y la documentación de Sanctum habla de _abilities_ en los tokens. Era fácil terminar con roles duplicados por guard, o con abilities usadas como si fueran roles.

## Decisión

1. **Un solo guard: `web`.** Todos los roles de Spatie se crean con guard `web`. El panel (sesión) y la app (token) pasan por `auth:sanctum` y resuelven el mismo `User`, así que ese usuario ya trae sus roles. No se crean roles con guard `sanctum`.
2. **Los roles dicen qué puede hacer la persona; las abilities dicen qué puede hacer ese token.** Un encargado de área tiene el rol `encargado`; el token que usa desde el celular tiene solo `lotes:read`. En el backend se validan **ambas** cosas: el rol (middleware `role:`) y la ability (`$request->user()->tokenCan('lotes:read')`), porque el mismo encargado desde el panel sí puede capturar.
3. Los roles viven en el enum `App\Enums\Rol` (`administrador`, `ventas`, `encargado`, `cliente`) y los siembra `RolesSeeder`, que es idempotente. El tipo `Rol` en `@aleacion-prima/types` es la copia para el frontend.
4. **Por ahora solo hay roles.** Los permisos finos de Spatie (`lotes.crear`, `costos.ver`...) se agregan con cada historia de usuario que los necesite, no por adelantado.

## Consecuencias

- Alias de middleware en `bootstrap/app.php`: `role`, `permission`, `role_or_permission`. Uso: `->middleware(['auth:sanctum', 'role:administrador|ventas'])`.
- Cuando se agregue un rol o permiso: enum → seeder → tipo en `packages/types` → test en `RolesTest`. Los cuatro en el mismo PR.
- Un token de la app sin la ability correcta recibe 403 aunque el usuario tenga el rol. Ocultar botones en la UI no sustituye esto.
- Si algún día un cliente entra al panel (chat de cotizaciones), es el mismo guard: solo cambia lo que ve según su rol.

## Alternativas descartadas

- **Roles por guard (`web` y `sanctum`)**: obliga a asignar cada rol dos veces y a sincronizarlos; Spatie lo permite pero aquí no aporta nada porque el usuario es el mismo.
- **Usar abilities como roles** (`['administrador']` en el token): las abilities solo existen en tokens, no en sesión, así que el panel se quedaría sin roles.
- **RBAC propio con una columna `rol`**: más simple al inicio, pero habría que reescribirlo en cuanto aparezca el primer permiso fino. El anteproyecto ya justifica Spatie.
