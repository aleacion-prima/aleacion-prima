# ADR-0016: Feature flags con Pennant para prender y apagar módulos

- **Estado:** aceptada
- **Alcance:** `api`, `panel`

## Contexto

El sistema se entrega por módulos (lotes, inventario, ventas…) y no todos están listos al mismo tiempo. Ventas en particular llega después de la producción, pero sus rutas, menús y páginas se van construyendo antes de que el módulo esté terminado. Necesitamos poder tener ese código en `main` sin que quede expuesto al usuario, y poder prenderlo o apagarlo sin volver a desplegar ni editar código.

La decisión es **global**, no por usuario: un módulo está disponible para todos o para nadie. No queremos segmentar por persona, porcentaje ni A/B testing; solo un interruptor por módulo.

## Decisión

1. **Laravel Pennant** como mecanismo de feature flags, con el store `database` (tabla `features`). Es parte del ecosistema de Laravel, ya documentado, y evita inventar nuestra propia tabla de interruptores.
2. **Un módulo = una bandera.** Los módulos se listan en `config/modulos.php` (hoy solo `ventas`); el nombre del módulo es el nombre de la bandera. `App\Support\Modulos` centraliza los nombres, la validación (`existe`) y el estado (`estado()`).
3. **Scope global:** `Feature::resolveScopeUsing(fn () => null)`. La bandera vale igual para todos; no se resuelve por usuario.
4. **Todos los módulos arrancan apagados:** `Feature::define($modulo, fn () => false)` para cada uno. Un módulo nuevo nace invisible hasta que se prende a propósito.
5. **Se prende y apaga por consola:** `php artisan modulos:activar <modulo>` y `modulos:desactivar <modulo>`. Rechazan un módulo que no esté en `config/modulos.php`.
6. **Las rutas del módulo nacen protegidas.** En `routes/api.php` hay un grupo con el middleware `EnsureFeaturesAreActive::using('ventas')`: los endpoints de ventas se escriben dentro de ese grupo, así que con la bandera apagada no responden (400) aunque alguien los llame directo. El gating no depende de ocultar botones.
7. **El panel conoce el estado por `/api/user`.** `UsuarioResource` incluye `modulos: { ventas: false, … }`. El menú lateral oculta las secciones con `modulo` apagado y el router bloquea las rutas con `meta.modulo` apagado (redirige a `/`).
8. **Tiempo real:** al cambiar una bandera, Pennant emite `FeatureUpdated` / `FeatureUpdatedForAllScopes`; el listener `DifundirModulos` difunde `ModulosActualizados` por el canal privado `modulos`. El panel, suscrito tras el login, actualiza el menú sin recargar.

## Consecuencias

- Un módulo a medio hacer puede vivir en `main` detrás de su bandera, apagada, sin exponerse. Encaja con GitHub Flow y Squash and merge (ADR-0014).
- **El gating real está en el backend** (middleware + canal privado). El panel solo reacciona; nunca es la única barrera, igual que el "solo lectura" de la app móvil.
- La tabla `features` es parte del estado de la BD: el interruptor persiste entre despliegues. En desarrollo se prende con el comando contra la BD local de Docker.
- El canal `modulos` sigue la regla de ADR-0005: privado, autorizado por `auth:sanctum`, y `broadcastWith()` manda solo el mapa de módulos, nada más.
- Cada módulo nuevo es una línea en `config/modulos.php` más su `Feature::define`. Al agregarlo, nace apagado por defecto.

## Alternativas descartadas

- **Variables de entorno (`MODULO_VENTAS=true`)**: cambiar un módulo exigiría editar `.env` y redesplegar, y no hay evento para avisar al panel en vivo. Pennant lo hace en caliente.
- **Columna propia o tabla a mano**: reinventa lo que Pennant ya da (comandos, eventos, scopes, store configurable) sin ninguna ventaja.
- **Flags por usuario / porcentajes**: Pennant los soporta, pero no los necesitamos; el scope global mantiene la lógica simple y la decisión en un solo lugar.
- **Solo ocultar en el frontend**: dejaría los endpoints accesibles. El gating tiene que estar en la API.
