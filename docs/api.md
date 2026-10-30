# API

Contrato de la API: qué rutas existen, qué rol las usa y qué requerimientos cubren. Es el acuerdo entre el backend y los tres frontends (panel, app móvil y sitio público). Las secciones de Autenticación, Catálogos e Inventario reflejan lo implementado en el Sprint 1; el resto sigue siendo el contrato planeado. La documentación OpenAPI generada por Scramble está en `/docs/api` (solo en local).

Los RF y sus actores salen de [requerimientos.md](requerimientos.md), los roles y el middleware de [autenticacion.md](autenticacion.md) y las operaciones sobre un lote de [flujo-lote.md](flujo-lote.md).

## Convenciones

- Prefijo `/api`, cuerpo y respuestas en JSON. Las rutas fuera de `/api` son `/sanctum/csrf-cookie`, `/login`, `/logout` y `/broadcasting/auth`: el inicio y cierre de sesión del panel van en `routes/web.php` porque usan la sesión con cookie de Sanctum.
- En la columna **Rol** aparecen todos los roles que pueden usar la ruta. El administrador hereda las acciones de Ventas y de Encargado de área, por eso aparece también en sus rutas.
- `{codigo}` es el código del lote (`LOT-AAAAMMDD-NN`). `{parada}` es el identificador de la parada dentro del recorrido del lote, no el del área, porque un recorrido puede repetir un área.
- Las rutas del panel usan cookie de sesión y `auth:sanctum`. Las de la app móvil usan token Bearer con `auth:sanctum` y `ability:lotes:read`. Las del sitio público no llevan autenticación.
- Los RF con actor Sistema no tienen ruta propia: son reglas que ejecutan los servicios. Se anotan en la columna **RF** de la ruta que las dispara.
- Ninguna ruta de lectura de lote entrega costos al encargado de área ni a la app móvil (propuesta, ver Pendiente).

## Autenticación

Sin middleware de rol salvo que se indique. Detalle de cookies, CSRF y tokens en [autenticacion.md](autenticacion.md).

| Método | Ruta                   | Rol                                           | RF                              |
| ------ | ---------------------- | --------------------------------------------- | ------------------------------- |
| `GET`  | `/sanctum/csrf-cookie` | Todos (sin sesión)                            | RF-01                           |
| `POST` | `/login`               | Todos (sin sesión)                            | RF-01                           |
| `POST` | `/api/movil/login`     | Encargado de área, Administrador (sin sesión) | RF-04, RF-43                    |
| `POST` | `/logout`              | Todos                                         | RF-05                           |
| `GET`  | `/api/user`            | Todos                                         | RF-03 (devuelve rol y `activo`) |

## Catálogos

Middleware: `auth:sanctum` y `role:administrador` en todas, incluidas las lecturas (ver Pendiente: lectura de catálogos para Ventas). La publicación de productos está planeada para Ventas y Administrador.

| Método   | Ruta                                    | Rol                   | RF           |
| -------- | --------------------------------------- | --------------------- | ------------ |
| `GET`    | `/api/areas`                            | Administrador         | RF-06        |
| `GET`    | `/api/areas/{area}`                     | Administrador         | RF-06        |
| `POST`   | `/api/areas`                            | Administrador         | RF-06        |
| `PUT`    | `/api/areas/{area}`                     | Administrador         | RF-06        |
| `DELETE` | `/api/areas/{area}`                     | Administrador         | RF-06        |
| `GET`    | `/api/productos`                        | Administrador         | RF-07        |
| `GET`    | `/api/productos/opciones`               | Administrador         | RF-07        |
| `GET`    | `/api/productos/{producto}`             | Administrador         | RF-07        |
| `POST`   | `/api/productos`                        | Administrador         | RF-07        |
| `PUT`    | `/api/productos/{producto}`             | Administrador         | RF-07        |
| `DELETE` | `/api/productos/{producto}`             | Administrador         | RF-07        |
| `PATCH`  | `/api/productos/{producto}/publicacion` | Ventas, Administrador | RF-52        |
| `GET`    | `/api/insumos`                          | Administrador         | RF-08        |
| `GET`    | `/api/insumos/{insumo}`                 | Administrador         | RF-08        |
| `POST`   | `/api/insumos`                          | Administrador         | RF-08        |
| `PUT`    | `/api/insumos/{insumo}`                 | Administrador         | RF-08        |
| `DELETE` | `/api/insumos/{insumo}`                 | Administrador         | RF-08        |
| `GET`    | `/api/usuarios`                         | Administrador         | RF-09        |
| `GET`    | `/api/areas-disponibles`                | Administrador         | RF-09, RF-48 |
| `POST`   | `/api/usuarios`                         | Administrador         | RF-09, RF-48 |
| `PUT`    | `/api/usuarios/{usuario}`               | Administrador         | RF-09, RF-48 |
| `DELETE` | `/api/usuarios/{usuario}`               | Administrador         | RF-09        |

- `DELETE` no borra en ningún catálogo: desactiva (`activo = false`). En usuarios, además, revoca sus tokens.
- El recorrido por defecto y la receta de insumos de un producto viajan dentro de `/api/productos` (RF-07); no tienen ruta propia. `GET /api/productos/opciones` devuelve las áreas e insumos activos para armarlos.
- Las áreas de un encargado viajan como `area_ids` dentro de `POST` y `PUT /api/usuarios` (RF-48); `GET /api/areas-disponibles` devuelve las áreas activas para elegirlas.

## Inventario

Middleware: `auth:sanctum` y `role:administrador` en escritura; `role:ventas|administrador` en lectura.

| Método | Ruta                                | Rol                   | RF                  |
| ------ | ----------------------------------- | --------------------- | ------------------- |
| `POST` | `/api/insumos/{insumo}/entradas`    | Administrador         | RF-10, RF-11, RF-14 |
| `POST` | `/api/insumos/{insumo}/ajustes`     | Administrador         | RF-12, RF-14        |
| `GET`  | `/api/insumos/{insumo}/movimientos` | Administrador         | RF-11               |
| `GET`  | `/api/inventario/insumos`           | Ventas, Administrador | RF-13, RF-11        |
| `GET`  | `/api/inventario/productos`         | Ventas, Administrador | RF-13, RF-15        |

## Lotes

Middleware: `auth:sanctum`. Rol según la fila. La lectura de un lote (`GET /api/lotes/{codigo}` y su historial) la comparten el panel y la app móvil.

| Método | Ruta                            | Rol                                      | RF                                |
| ------ | ------------------------------- | ---------------------------------------- | --------------------------------- |
| `GET`  | `/api/lotes`                    | Administrador, Ventas, Encargado de área | RF-34, RF-35                      |
| `POST` | `/api/lotes`                    | Administrador                            | RF-16, RF-17, RF-18, RF-19, RF-24 |
| `GET`  | `/api/lotes/{codigo}`           | Administrador, Ventas, Encargado de área | RF-20, RF-35                      |
| `GET`  | `/api/lotes/{codigo}/historial` | Administrador, Ventas, Encargado de área | RF-35                             |
| `GET`  | `/api/lotes/{codigo}/bitacora`  | Administrador, Ventas                    | RF-35, RF-36                      |
| `GET`  | `/api/lotes/{codigo}/costo`     | Administrador, Ventas                    | RF-32, RF-33, RF-35               |
| `POST` | `/api/lotes/{codigo}/confirmar` | Administrador                            | RF-22, RF-24, RF-15, RF-33        |
| `POST` | `/api/lotes/{codigo}/cancelar`  | Administrador                            | RF-21, RF-24                      |
| `POST` | `/api/lotes/{codigo}/ajustes`   | Administrador                            | RF-23, RF-32, RF-36               |

La etiqueta con el código QR (RF-20) la genera el panel con `qrcode.vue` a partir de los datos de `GET /api/lotes/{codigo}`; no necesita ruta propia.

## Captura por área

Middleware: `auth:sanctum` y `role:encargado|administrador`. El encargado solo captura en las áreas que tiene asignadas (RF-48); el administrador captura en cualquiera.

| Método | Ruta                                               | Rol                              | RF                                                     |
| ------ | -------------------------------------------------- | -------------------------------- | ------------------------------------------------------ |
| `POST` | `/api/lotes/{codigo}/paradas/{parada}/entrada`     | Encargado de área, Administrador | RF-25, RF-29, RF-30, RF-48, RF-50, RF-51               |
| `POST` | `/api/lotes/{codigo}/paradas/{parada}/salida`      | Encargado de área, Administrador | RF-26, RF-27, RF-28, RF-29, RF-30, RF-48, RF-49, RF-50 |
| `POST` | `/api/lotes/{codigo}/paradas/{parada}/imprevistos` | Encargado de área, Administrador | RF-31, RF-37                                           |
| `GET`  | `/api/lotes/{codigo}/imprevistos`                  | Encargado de área, Administrador | RF-31                                                  |

El retorno de metal (RF-27) viaja como `retorno_kg` en la salida de la parada de Desmolde.

## Tablero y notificaciones

Middleware: `auth:sanctum` y `role:administrador`.

| Método  | Ruta                             | Rol           | RF           |
| ------- | -------------------------------- | ------------- | ------------ |
| `GET`   | `/api/tablero`                   | Administrador | RF-34        |
| `GET`   | `/api/notificaciones`            | Administrador | RF-38, RF-37 |
| `PATCH` | `/api/notificaciones/{id}/leida` | Administrador | RF-38        |
| `POST`  | `/api/notificaciones/leidas`     | Administrador | RF-38        |
| `POST`  | `/broadcasting/auth`             | Administrador | RF-34, RF-37 |

`GET /api/tablero` carga el estado inicial; después el tablero se mantiene al día por WebSocket.

## Aplicación móvil

Middleware: `auth:sanctum`, `role:encargado|administrador` y `ability:lotes:read`. La app solo lee: no tiene ninguna ruta de escritura.

| Método | Ruta                            | Rol                                           | RF           |
| ------ | ------------------------------- | --------------------------------------------- | ------------ |
| `POST` | `/api/movil/login`              | Encargado de área, Administrador (sin sesión) | RF-04, RF-43 |
| `GET`  | `/api/lotes/{codigo}`           | Encargado de área, Administrador              | RF-44, RF-45 |
| `GET`  | `/api/lotes/{codigo}/historial` | Encargado de área, Administrador              | RF-45        |
| `POST` | `/api/logout`                   | Encargado de área, Administrador              | RF-05        |

El escaneo del QR (RF-44) ocurre en el dispositivo; el resultado es el `{codigo}` con el que la app llama a la API.

## Sitio público

Sin autenticación. Solo rutas de lectura.

| Método | Ruta                                | Rol      | RF           |
| ------ | ----------------------------------- | -------- | ------------ |
| `GET`  | `/api/publico/productos`            | Invitado | RF-46, RF-52 |
| `GET`  | `/api/publico/productos/{producto}` | Invitado | RF-46        |
| `GET`  | `/api/publico/contacto`             | Invitado | RF-47        |

Contrato de los campos (RNF-04):

- Productos públicos: `id`, `nombre`, `especificaciones` y `disponible`. Solo se listan los productos publicados (RF-52).
- Contacto: correo, teléfono y horario de atención del personal de ventas.
- Ninguna respuesta pública incluye precios, costos, tarifas, lotes ni existencias de insumos. Una prueba automatizada lo verifica en cada cambio.

## Tiempo real

| Canal                 | Acceso                            | Eventos                                                           | RF            |
| --------------------- | --------------------------------- | ----------------------------------------------------------------- | ------------- |
| `catalogo`            | Público, sin `/broadcasting/auth` | `disponibilidad.actualizada` con solo `{ id, disponible }`        | RF-46, RNF-04 |
| `tablero` (propuesta) | Privado, administrador            | `LoteActualizado`, `ImprevistoRegistrado`, `StockMinimoAlcanzado` | RF-34, RF-37  |

## Ventas (recortable)

Se recorta en este orden si el tiempo no alcanza: chat de cotizaciones, clientes y pedidos, reporte de ventas. Middleware: `auth:sanctum` y `role:ventas|administrador`, salvo el chat, que también admite `cliente`.

| Método   | Ruta                                        | Rol                   | RF    |
| -------- | ------------------------------------------- | --------------------- | ----- |
| `GET`    | `/api/clientes`                             | Ventas, Administrador | RF-39 |
| `POST`   | `/api/clientes`                             | Ventas, Administrador | RF-39 |
| `PUT`    | `/api/clientes/{cliente}`                   | Ventas, Administrador | RF-39 |
| `DELETE` | `/api/clientes/{cliente}`                   | Ventas, Administrador | RF-39 |
| `GET`    | `/api/pedidos`                              | Ventas, Administrador | RF-40 |
| `POST`   | `/api/pedidos`                              | Ventas, Administrador | RF-40 |
| `PUT`    | `/api/pedidos/{pedido}`                     | Ventas, Administrador | RF-40 |
| `PATCH`  | `/api/pedidos/{pedido}/pago`                | Ventas, Administrador | RF-40 |
| `GET`    | `/api/reportes/ventas`                      | Ventas, Administrador | RF-41 |
| `GET`    | `/api/cotizaciones`                         | Ventas, Cliente       | RF-42 |
| `GET`    | `/api/cotizaciones/{conversacion}/mensajes` | Ventas, Cliente       | RF-42 |
| `POST`   | `/api/cotizaciones/{conversacion}/mensajes` | Ventas, Cliente       | RF-42 |

## Cobertura

- Todos los RF con actor persona tienen al menos una ruta: RF-01, RF-03 a RF-10, RF-12, RF-13, RF-16, RF-17, RF-20 a RF-23, RF-25 a RF-27, RF-31, RF-34, RF-35, RF-38 a RF-47 y RF-52.
- RF con actor Sistema, sin ruta propia: RF-02 (middleware de rol), RF-11, RF-14, RF-15, RF-18, RF-19, RF-24, RF-28 a RF-30, RF-32, RF-33, RF-36, RF-37 y RF-48 a RF-51. Se disparan desde las rutas donde aparecen en la columna RF.
- RF-53 sigue pendiente de decisión ([requerimientos.md](requerimientos.md)); si entra, necesita una ruta de corrección de capturas de un lote por confirmar.

## Pendiente

- **Lectura de catálogos para Ventas.** El contrato inicial daba a Ventas la lectura de productos e insumos, y a Ventas y Encargado la de áreas; en el Sprint 1 se implementaron solo para Administrador. Se decide en la planeación del Sprint 2 si se abre el `GET` a esos roles.
- **`/api/areas-disponibles` contra `/api/areas?activo=1`.** Devuelven lo mismo para elegir áreas activas; conviene dejar solo una.

- **Quién crea lotes.** RF-16 dice Administrador; [flujo-lote.md](flujo-lote.md) y el diagrama de estados dicen Ventas o Administrador. Aquí se sigue el RF. Si ventas también crea, `POST /api/lotes` pasa a `role:ventas|administrador` y se actualizan RF-16 y autenticacion.md.
- **Costos para el encargado.** Se propone que `historial` y `GET /api/lotes/{codigo}` no devuelvan costos al encargado de área ni a la app móvil; solo `costo` y `bitacora` los exponen, y solo a Ventas y Administrador. Confirmar con el equipo.
- **Ability en el panel.** `ability:lotes:read` aplica a tokens de la app; confirmar con una prueba que las peticiones con cookie del panel pasan por las mismas rutas de lectura.
- **Nombre del canal privado.** `tablero` es una propuesta; se fija al implementar los canales de `routes/channels.php`.
- **Rol ventas en autenticacion.md.** Su fila no menciona RF-13 ni RF-35 (ya anotado en requerimientos.md).
