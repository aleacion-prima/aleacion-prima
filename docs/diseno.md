# Guía visual

Colores, tipografías y reglas de uso compartidas por el panel, la app móvil y el sitio público. El maquetado en Figma y el código usan los mismos nombres y valores: en Figma como Variables de color, en el panel en `apps/panel/src/css/quasar.variables.scss` y en el sitio como variables CSS.

El maquetado vive en el archivo de Figma [Maquetado](https://www.figma.com/design/GLTcrY3WSW5avgfKLDU5Y8/Maquetado): la página "Sistema de diseño" tiene las variables, los estilos de texto y los componentes; la página "Pantallas" tiene las pantallas y el prototipo.

## Idea

Neutros fríos de acero y grafito, como las pantallas de control de una planta, y un solo color caliente: el del hierro fundido. El naranja se reserva para lo que importa (acciones principales y lotes en proceso); todo lo demás se mantiene en grises para que el color caliente se note.

## Paleta

| Nombre         | Hex       | Variable de Quasar | Uso                                                |
| -------------- | --------- | ------------------ | -------------------------------------------------- |
| Hierro fundido | `#C2410C` | `$primary`         | Botones principales, enlaces, foco                 |
| Colada         | `#F97316` | `$accent`          | Lote en proceso, parada activa, resaltes           |
| Grafito        | `#334155` | `$secondary`       | Barra superior, menú lateral, encabezados de tabla |
| Grafito oscuro | `#0F172A` | `$dark`            | Texto principal; fondo del modo oscuro             |
| Grafito medio  | `#475569` | —                  | Texto secundario, iconos inactivos                 |
| Acero claro    | `#E2E8F0` | —                  | Bordes, divisores, filas alternas                  |
| Blanco humo    | `#F8FAFC` | —                  | Fondo de página                                    |
| Blanco         | `#FFFFFF` | —                  | Tarjetas, campos de formulario, fondo de tablas    |

## Colores semánticos

Cada estado del lote y cada aviso tiene un color fijo, el mismo en el panel, la app y el sitio.

| Significado                                   | Hex       | Variable de Quasar |
| --------------------------------------------- | --------- | ------------------ |
| `terminado`, confirmado, disponible           | `#15803D` | `$positive`        |
| `cancelado`, error, rechazado                 | `#B91C1C` | `$negative`        |
| `por_confirmar`, stock mínimo, captura tardía | `#A16207` | `$warning`         |
| `creado`, informativo                         | `#0369A1` | `$info`            |
| `en_proceso`                                  | `#F97316` | `$accent`          |

## Reglas de contraste

Toda combinación de texto y fondo cumple WCAG AA (4.5:1 para texto normal). Medidas:

| Texto          | Fondo          | Relación | Uso permitido                   |
| -------------- | -------------- | -------- | ------------------------------- |
| Blanco         | Hierro fundido | 5.18     | Botones principales             |
| Blanco         | Grafito        | 10.35    | Barra y menú                    |
| Blanco         | `$positive`    | 5.02     | Chips de estado                 |
| Blanco         | `$negative`    | 6.47     | Chips de estado                 |
| Blanco         | `$warning`     | 4.92     | Chips de estado                 |
| Blanco         | `$info`        | 5.93     | Chips de estado                 |
| Grafito oscuro | Colada         | 6.37     | Chips de `en_proceso`, resaltes |
| Grafito oscuro | Blanco humo    | 17.06    | Texto principal                 |
| Grafito medio  | Blanco humo    | 7.24     | Texto secundario                |
| Hierro fundido | Blanco humo    | 4.95     | Enlaces                         |

- **Sobre Colada nunca va texto blanco** (2.80). Colada lleva texto Grafito oscuro, o se usa como relleno de chips e iconos.
- **`$warning` y Colada no van juntos en la misma tarjeta**: un ámbar y un naranja lado a lado se confunden.
- El foco de teclado se dibuja con Hierro fundido, 2 px, siempre visible.

## Tipografía

| Rol     | Fuente                | Dónde                                                 |
| ------- | --------------------- | ----------------------------------------------------- |
| Cuerpo  | Roboto                | Todo el texto; ya viene con Quasar                    |
| Títulos | Barlow Semi Condensed | Títulos de página y de tarjeta, pesos 600 y 700       |
| Códigos | IBM Plex Mono         | Códigos de lote, producto e insumo; números en tablas |

Las tres son gratuitas (Google Fonts). Los códigos (`LOT-20260924-01`, `CHT-FUN`) siempre en monoespaciada: alinean en tablas y se leen en la etiqueta con QR.

## En el panel

Ya está aplicado en `apps/panel`: los colores son las variables de Quasar (`color="positive"`, `text-negative`, `bg-warning`), las clases `text-h1` a `text-h6` usan Barlow, la clase `codigo` pone IBM Plex Mono y `text-secundario` pinta en Grafito medio. Los componentes del Figma que Quasar no trae viven en `src/components` por dominio: `ChipEstado`, `ParadaRecorrido`, `RecorridoLote`, `PasoRecorrido`, `EtiquetaLote`, `EstadoVacio`, `AvisoSistema` y `MenuLateral`. Las animaciones están en `src/css/animaciones.scss` y se desactivan con `prefers-reduced-motion`. La ruta `/muestrario`, solo en desarrollo, los enseña todos.

## Sitio público

Misma paleta y mismas fuentes. La disponibilidad usa `$positive` (disponible) y Grafito medio (sin existencia). Nunca se muestran precios ni costos, así que no hay color para ellos.

## Modo oscuro

Pendiente. Las variables están definidas para que Quasar lo genere con `$dark` y `$dark-page`; se revisa cuando el panel tenga pantallas reales.
