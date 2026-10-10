# ADR-0005: Tiempo real con Reverb; el canal público transmite lo mínimo, nunca precios

- **Estado:** aceptada
- **Alcance:** `api`, `panel`, `web`

## Contexto

Tres cosas necesitan tiempo real: el tablero del administrador (estado de lotes, imprevistos), el centro de notificaciones y la disponibilidad de productos en el sitio público. El sitio es estático y sin sesión, así que cualquier persona puede suscribirse a lo que él escuche. El anteproyecto prohíbe mostrar precios en el sitio público y ya justifica Reverb frente a Pusher.

## Decisión

1. **Laravel Reverb** como servidor de WebSockets, con `BROADCAST_CONNECTION=reverb`. Clientes con Laravel Echo + `pusher-js` en el panel y en el sitio.
2. **Dos tipos de canal:**
   - **Privados** (`private-...`) para el panel: tablero, notificaciones, chat de cotizaciones. La autorización pasa por `/broadcasting/auth` con el middleware `auth:sanctum` (misma cookie del panel).
   - **Público** `catalogo` para el sitio: solo disponibilidad de productos.
3. **Todo evento que salga por un canal público define `broadcastWith()` y manda lo indispensable.** El evento `disponibilidad.actualizada` transmite `{ id, disponible }` y nada más. Nunca el modelo completo, nunca precios ni costos.
4. Los nombres de evento se fijan con `broadcastAs()` (en el cliente se escuchan con punto inicial: `.disponibilidad.actualizada`) para no exponer el namespace de PHP.
5. En el sitio, Echo se crea de forma **perezosa** (`obtenerEcho()`), sin `authorizer` ni cookies, y solo dentro de islas de Vue con directiva `client:*`.

## Consecuencias

- **Lo que se emite en `catalogo` es público.** En cada PR que agregue un evento público se revisa `broadcastWith()`.
- Los WebSockets no pasan por CORS; los valida Reverb con `REVERB_ALLOWED_ORIGINS` (compara solo el host).
- Llaves de Reverb: **fijas en desarrollo** (`aleacion` / `aleacion-local` / `aleacion-local-secret`, en `.env.example`) para que backend y fronts coincidan sin configurar nada; aleatorias en producción. La KEY es pública; el SECRET nunca llega a los fronts.
- Un `101 Switching Protocols` no prueba que la llave sea válida: Reverb acepta el socket y luego manda `pusher:error` (4001) si la app no existe. La prueba es ver `pusher:connection_established` en los mensajes.
- Los transportes de Echo se limitan según el esquema (`ws` en http, `wss` en https) para evitar reintentos fallidos a `wss://` en local.
- Cada evento en tiempo real tiene su tipo en `@aleacion-prima/types`, compartido entre API y fronts.

## Alternativas descartadas

- **Polling desde el sitio** (pedir la disponibilidad cada N segundos): más simple, pero el anteproyecto compromete tiempo real y con muchos visitantes carga la API sin necesidad.
- **Transmitir el modelo completo** (`broadcast(new ProductoActualizado($producto))` sin `broadcastWith`): filtraría precios y costos a cualquiera que abra el sitio.
- **Pusher u otro servicio externo**: costo mensual y datos fuera de la infraestructura propia; Reverb cubre lo mismo en el mismo servidor.
