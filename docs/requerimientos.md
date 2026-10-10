# Requerimientos

Requerimientos funcionales (RF) y no funcionales (RNF) del sistema. Es el mismo contenido de la sección 8 del anteproyecto: si cambia uno, se cambia el otro en el mismo día.

Última sincronización con el anteproyecto: 2 de octubre de 2026.

- Las reglas detrás de cada requerimiento están en [flujo-lote.md](./flujo-lote.md), [modelo-datos.md](./modelo-datos.md) y [autenticacion.md](./autenticacion.md).
- Los términos de la planta están en [glosario.md](./glosario.md).
- Los identificadores son estables: no se renumeran. Los RF-48 a RF-52 se agregaron después y viven dentro de su grupo.

## Actores

| Actor en este documento | Rol técnico (`App\Enums\Rol`) | Notas                                                       |
| ----------------------- | ----------------------------- | ----------------------------------------------------------- |
| Administrador           | `administrador`               | Hereda las acciones de Ventas y de Encargado de área        |
| Ventas                  | `ventas`                      |                                                             |
| Encargado de área       | `encargado`                   | Captura solo en las áreas que tiene asignadas (`area_user`) |
| Cliente                 | `cliente`                     | Recortable; solo existe si entra el chat de cotizaciones    |
| Invitado                | —                             | Sitio público, sin sesión                                   |
| Sistema                 | —                             | Reglas y validaciones que no inicia una persona             |

"Todos" significa cualquier usuario con sesión en el panel.

## Requerimientos funcionales

### Autenticación y roles

| ID    | Descripción                                                                                                                                 | Actor             |
| ----- | ------------------------------------------------------------------------------------------------------------------------------------------- | ----------------- |
| RF-01 | El sistema debe permitir el inicio de sesión mediante usuario y contraseña en el panel web.                                                 | Todos             |
| RF-02 | El sistema debe manejar control de acceso por roles: administrador, ventas y encargado de área en el panel, e invitado en el sitio público. | Sistema           |
| RF-03 | El sistema debe mostrar a cada rol únicamente las opciones y acciones permitidas según sus permisos.                                        | Todos             |
| RF-04 | El sistema debe emitir un token de solo lectura para la autenticación de la aplicación móvil.                                               | Encargado de área |
| RF-05 | El sistema debe permitir el cierre de sesión en el panel y en la aplicación móvil.                                                          | Todos             |

### Administración de catálogos

| ID    | Descripción                                                                                                         | Actor         |
| ----- | ------------------------------------------------------------------------------------------------------------------- | ------------- |
| RF-06 | El sistema debe permitir administrar las áreas de la planta con sus tarifas por hora de mano de obra y de máquina.  | Administrador |
| RF-07 | El sistema debe permitir administrar productos con sus especificaciones, recorrido por defecto y receta de insumos. | Administrador |
| RF-08 | El sistema debe permitir administrar insumos con su costo unitario y stock mínimo.                                  | Administrador |
| RF-09 | El sistema debe permitir administrar usuarios, roles y las áreas asignadas a cada encargado.                        | Administrador |
| RF-52 | El sistema debe permitir publicar u ocultar cada producto en el sitio público.                                      | Ventas        |

### Control de insumos e inventario

| ID    | Descripción                                                                                                                                       | Actor         |
| ----- | ------------------------------------------------------------------------------------------------------------------------------------------------- | ------------- |
| RF-10 | El sistema debe permitir registrar entradas de insumos indicando proveedor y lote del proveedor.                                                  | Administrador |
| RF-11 | El sistema debe calcular las existencias de insumos a partir de los movimientos registrados.                                                      | Sistema       |
| RF-12 | El sistema debe permitir ajustar existencias por auditoría física, registrando el motivo del ajuste.                                              | Administrador |
| RF-13 | El sistema debe permitir consultar las existencias de insumos y de producto terminado.                                                            | Ventas        |
| RF-14 | El sistema debe detectar cuando la existencia de un insumo llega a su stock mínimo o queda por debajo.                                            | Sistema       |
| RF-15 | El sistema debe calcular las existencias de producto terminado a partir de sus movimientos; la entrada se genera al confirmar el cierre del lote. | Sistema       |

### Gestión de lotes

| ID    | Descripción                                                                                                                                                                                          | Actor         |
| ----- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------- |
| RF-16 | El sistema debe permitir crear un lote a partir de un producto y una cantidad, clonando el recorrido por defecto y la receta de insumos, y asignarle un código único con el formato LOT-AAAAMMDD-NN. | Administrador |
| RF-17 | El sistema debe permitir ajustar el recorrido del lote al crearlo, incluyendo siempre las áreas obligatorias (moldeo, fusión, colada y desmolde).                                                    | Administrador |
| RF-18 | El sistema debe asignar automáticamente la materia prima al lote al crearlo, congelando el costo unitario de los insumos y las tarifas por hora vigentes.                                            | Sistema       |
| RF-19 | El sistema debe validar que el metal del lote no exceda la capacidad de una colada del horno.                                                                                                        | Sistema       |
| RF-20 | El sistema debe generar e imprimir una etiqueta con el código QR del lote.                                                                                                                           | Administrador |
| RF-21 | El sistema debe permitir cancelar un lote creado o en proceso registrando el motivo; los insumos se devuelven al inventario solo si el lote aún no sale de la primera parada.                        | Administrador |
| RF-22 | El sistema debe permitir confirmar el cierre del lote, momento en que las piezas entran al inventario de producto terminado y el costo queda congelado.                                              | Administrador |
| RF-23 | El sistema debe permitir registrar ajustes sobre un lote (valor anterior, valor nuevo, motivo y responsable) sin reabrir lotes terminados.                                                           | Administrador |
| RF-24 | El sistema debe gestionar el ciclo de vida del lote (creado, en proceso, por confirmar, terminado y cancelado), permitiendo solo las operaciones válidas en cada estado.                             | Sistema       |
| RF-49 | El sistema debe pasar el lote a "por confirmar" automáticamente al registrarse la salida de su última parada.                                                                                        | Sistema       |

Notas técnicas:

- RF-17: recorrido congelado en el lote y áreas obligatorias, ADR-0009.
- RF-19: el límite es `config('planta.capacidad_colada_kg')` (750 kg).
- RF-22 y RF-49: cierre disparado por la última parada y confirmado por el administrador, ADR-0010.
- RF-23: sin reapertura; ajustes con motivo y responsable, ADR-0011.

### Captura por área

| ID    | Descripción                                                                                                                                                                 | Actor             |
| ----- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ----------------- |
| RF-25 | El sistema debe permitir registrar la entrada a un área con las piezas recibidas y la hora en que ocurrió.                                                                  | Encargado de área |
| RF-26 | El sistema debe permitir registrar la salida de un área con las piezas entregadas, la merma y la hora en que ocurrió.                                                       | Encargado de área |
| RF-27 | El sistema debe permitir registrar, en la salida de desmolde, el metal recuperado (en kg) como retorno al inventario.                                                       | Encargado de área |
| RF-28 | El sistema debe validar que las piezas entregadas sean menores o iguales a las piezas recibidas en cada área.                                                               | Sistema           |
| RF-29 | El sistema debe registrar al encargado responsable de cada movimiento de entrada y salida.                                                                                  | Sistema           |
| RF-30 | El sistema debe permitir la captura tardía y marcarla como tal cuando pasen más de 30 minutos entre la hora en que ocurrió el movimiento y la hora en que se registró.      | Sistema           |
| RF-31 | El sistema debe permitir registrar imprevistos (falla de máquina, retraso de material, pieza defectuosa) ligados al lote y al área donde ocurren.                           | Encargado de área |
| RF-48 | El sistema debe permitir a cada encargado capturar únicamente en las áreas que tiene asignadas.                                                                             | Sistema           |
| RF-50 | El sistema debe validar que la salida de un área no sea anterior a su entrada y que ninguna hora capturada sea futura.                                                      | Sistema           |
| RF-51 | El sistema debe prellenar las piezas recibidas con las entregadas por el área anterior y, si el encargado captura otra cantidad, conservar la diferencia como discrepancia. | Sistema           |

Notas técnicas:

- RF-27: el retorno genera un movimiento `retorno` sobre el insumo de `config('planta.insumo_retorno')` y se resta del costo, [ADR-0012](./decisiones/0012-retornos.md).
- RF-30: el umbral es `config('planta.umbral_captura_tardia')` (30 minutos); se compara `ocurrido_en` contra `registrado_en`.

### Cálculo de costos

| ID    | Descripción                                                                                                                                                           | Actor   |
| ----- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------- |
| RF-32 | El sistema debe calcular automáticamente el costo real del lote considerando el material neto de retornos, las horas de mano de obra y las horas de máquina por área. | Sistema |
| RF-33 | El sistema debe calcular el costo por pieza al confirmar el cierre del lote.                                                                                          | Sistema |

Caso de referencia: el lote `LOT-20260924-01` debe dar $10,401.25 de costo total y $226.11 por pieza.

### Tablero, historial y notificaciones

| ID    | Descripción                                                                                          | Actor                  |
| ----- | ---------------------------------------------------------------------------------------------------- | ---------------------- |
| RF-34 | El sistema debe mostrar al administrador un tablero con el estado de todos los lotes en tiempo real. | Administrador          |
| RF-35 | El sistema debe permitir consultar el historial completo, la bitácora y el costo de cada lote.       | Administrador / Ventas |
| RF-36 | El sistema debe registrar una bitácora de eventos por lote.                                          | Sistema                |
| RF-37 | El sistema debe notificar en tiempo real los imprevistos, el stock mínimo y los lotes por confirmar. | Sistema                |
| RF-38 | El sistema debe contar con un centro de notificaciones donde queden registrados los avisos.          | Administrador          |

### Ventas (recortable)

Se recortan en este orden si el tiempo no alcanza: chat de cotizaciones, clientes y pedidos, reporte de ventas.

| ID    | Descripción                                                                                                                                               | Actor            |
| ----- | --------------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------- |
| RF-39 | El sistema debe permitir administrar clientes empresariales con sus datos fiscales, contactos y condiciones de pago.                                      | Ventas           |
| RF-40 | El sistema debe permitir administrar pedidos de clientes vinculados a lotes de producción y al inventario, con registro de venta y estado de pago manual. | Ventas           |
| RF-41 | El sistema debe ofrecer un reporte básico de ventas.                                                                                                      | Ventas           |
| RF-42 | El sistema debe permitir atender un chat de cotizaciones entre clientes registrados (con rol de cliente en el panel) y el personal de ventas.             | Ventas / Cliente |

### Aplicación móvil (solo lectura)

| ID    | Descripción                                                                                                                                              | Actor             |
| ----- | -------------------------------------------------------------------------------------------------------------------------------------------------------- | ----------------- |
| RF-43 | La aplicación móvil debe permitir iniciar sesión con usuario y contraseña; la sesión es de solo lectura.                                                 | Encargado de área |
| RF-44 | La aplicación móvil debe permitir escanear el código QR del lote con la cámara del dispositivo.                                                          | Encargado de área |
| RF-45 | La aplicación móvil debe permitir consultar el estado, el área actual y el historial del lote escaneado, sin permitir capturar ni modificar información. | Encargado de área |

### Sitio público

| ID    | Descripción                                                                                                                                             | Actor    |
| ----- | ------------------------------------------------------------------------------------------------------------------------------------------------------- | -------- |
| RF-46 | El sitio público debe mostrar el catálogo de productos con sus especificaciones técnicas y su disponibilidad en tiempo real, sin precios.               | Invitado |
| RF-47 | El sitio público debe ofrecer un botón de cotización que muestre los datos de contacto del personal de ventas (correo, teléfono y horario de atención). | Invitado |

## Requerimientos no funcionales

| ID     | Categoría      | Descripción                                                                                                                                                                                                                                             |
| ------ | -------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| RNF-01 | Seguridad      | El sistema debe almacenar las contraseñas únicamente como hash (bcrypt), nunca en texto plano ni en las respuestas de la API.                                                                                                                           |
| RNF-02 | Seguridad      | Toda la comunicación del panel, la aplicación móvil, el sitio público y los WebSockets debe viajar cifrada (HTTPS y WSS), con certificados TLS renovados automáticamente.                                                                               |
| RNF-03 | Seguridad      | El servidor debe validar en cada petición la autenticación, el rol y, en la aplicación móvil, el alcance del token; ocultar opciones en la interfaz no sustituye esa validación.                                                                        |
| RNF-04 | Seguridad      | Ninguna respuesta pública ni evento del canal público de tiempo real debe incluir precios ni costos; una prueba automatizada lo verifica en cada cambio.                                                                                                |
| RNF-05 | Integridad     | Cada operación sobre un lote o sobre el inventario debe ejecutarse en una transacción: se completa entera o no deja cambios. La bitácora y los imprevistos se escriben en MongoDB solo después de confirmarse la transacción en PostgreSQL.             |
| RNF-06 | Integridad     | Los movimientos de inventario no se editan ni se borran; toda corrección queda como ajuste con motivo, responsable y fecha, y las existencias y los costos deben poder reconstruirse a partir de los movimientos.                                       |
| RNF-07 | Integridad     | Los importes y las cantidades deben guardarse y calcularse con decimales de precisión fija, no con punto flotante. Una prueba automatizada verifica que el lote de referencia dé $10,401.25 de costo total y $226.11 por pieza.                         |
| RNF-08 | Rendimiento    | Los cambios de estado de un lote, los imprevistos, las alertas de stock mínimo y la disponibilidad del catálogo deben reflejarse en el tablero y en el sitio en un máximo de 2 segundos, sin recargar la página.                                        |
| RNF-09 | Rendimiento    | La API debe responder las capturas y consultas en menos de 1 segundo, medido en el servidor de producción con los datos de demostración.                                                                                                                |
| RNF-10 | Rendimiento    | El sitio público debe obtener al menos 90 puntos de rendimiento en Lighthouse (móvil).                                                                                                                                                                  |
| RNF-11 | Disponibilidad | Debe generarse un respaldo diario automático de PostgreSQL y de MongoDB, conservando los últimos 7.                                                                                                                                                     |
| RNF-12 | Disponibilidad | Cada versión se despliega como imagen etiquetada; si una versión falla, debe poder regresarse a la anterior cambiando la etiqueta, sin reconstruir la imagen.                                                                                           |
| RNF-13 | Usabilidad     | Toda la interfaz debe estar en español de México, con el vocabulario de la planta definido en el glosario; cada mensaje de validación indica la regla incumplida y el valor de referencia.                                                              |
| RNF-14 | Compatibilidad | El panel y el sitio deben funcionar en las dos versiones más recientes de Chrome, Edge y Firefox; la aplicación móvil, en Android 8.0 o superior.                                                                                                       |
| RNF-15 | Mantenibilidad | Todo cambio a la rama principal debe entrar por Pull Request con una revisión y con la integración continua aprobada (formato, análisis estático, tipos y pruebas); la documentación de la API se genera automáticamente desde el código.               |
| RNF-16 | Privacidad     | El sitio público debe mostrar un aviso de privacidad conforme a la LFPDPPP y todos los datos de demostración deben ser ficticios. (Recortable) Los datos fiscales de los clientes se guardan cifrados y solo son visibles para administración y ventas. |

## Casos de uso

Los diagramas agrupan por módulo los RF cuyo actor es una persona. Los RF con actor "Sistema" son reglas y validaciones, y no aparecen en ellos.

| Diagrama                                                                                      | Cubre                                                  |
| --------------------------------------------------------------------------------------------- | ------------------------------------------------------ |
| [casos-de-uso-lotes.puml](./diagramas/casos-de-uso/casos-de-uso-lotes.puml)                   | Lotes y captura por área                               |
| [casos-de-uso-administracion.puml](./diagramas/casos-de-uso/casos-de-uso-administracion.puml) | Acceso, catálogos, inventario, notificaciones y ventas |
| [casos-de-uso-movil-sitio.puml](./diagramas/casos-de-uso/casos-de-uso-movil-sitio.puml)       | Aplicación móvil y sitio público                       |

## Trazabilidad con los objetivos

Los objetivos específicos son los siete del anteproyecto, en su numeración original.

| Objetivo específico                              | Requerimientos                                   |
| ------------------------------------------------ | ------------------------------------------------ |
| 1. Piezas, merma, tiempos y responsable por área | RF-25, RF-26, RF-28 a RF-30, RF-48, RF-50, RF-51 |
| 2. Insumos, retornos y costo real por lote       | RF-10 a RF-24, RF-27, RF-32, RF-33, RF-49        |
| 3. Imprevistos ligados al lote y al área         | RF-31, RF-37, RF-38                              |
| 4. Pedidos de clientes (recortable)              | RF-39, RF-40, RF-42                              |
| 5. Tablero, historial y reportes                 | RF-34 a RF-36, RF-41                             |
| 6. Consulta desde la aplicación móvil            | RF-04, RF-43 a RF-45                             |
| 7. Sitio público con catálogo                    | RF-46, RF-47, RF-52                              |

RF-01 a RF-03, RF-05 a RF-09 son de soporte (acceso y catálogos) y sirven a todos los objetivos.

## Pendiente

- Decidir si entra el RF-53: "El sistema debe permitir al administrador corregir las capturas de un lote por confirmar antes de confirmar su cierre" (ADR-0010; falta la validación del equipo). Si entra, va al final de "Gestión de lotes" y como caso de uso del Administrador en el diagrama de lotes.
- RNF-14: confirmar la versión mínima de Android contra `minSdkVersion` en `apps/panel/src-capacitor/android/variables.gradle`.
- RNF-08 a RNF-11: los umbrales (2 s, 1 s, 90 puntos, 7 respaldos) son una propuesta; ajustarlos cuando haya mediciones del primer despliegue.
- [autenticacion.md](./autenticacion.md): la fila del rol `ventas` no menciona la consulta de existencias (RF-13) ni el historial y costo del lote (RF-35).
