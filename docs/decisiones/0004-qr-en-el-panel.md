# ADR-0004: El QR se genera en el panel y solo contiene el código del lote

- **Estado:** aceptada
- **Alcance:** `api`, `panel`

## Contexto

Cada lote lleva una etiqueta física con un QR que los encargados escanean desde la app. El anteproyecto original proponía generar el QR en Laravel con **Simple QrCode**, pero ese paquete está estancado en `bacon/bacon-qr-code ^2.0` y choca con paquetes actuales que exigen `^3.0`. Además, la etiqueta se imprime desde el navegador del panel, así que generar la imagen en el servidor implicaba una petición extra por etiqueta y decidir dónde guardarla.

## Decisión

1. **El QR se genera en el frontend del panel** con `qrcode.vue` (componente `QrcodeSvg`): salida SVG para que imprima nítido, nivel de corrección `Q` porque la etiqueta va a rayarse y mancharse en planta, y margen `4` para que ML Kit lo detecte rápido.
2. **El QR contiene únicamente el código del lote** (p. ej. `LOT-2026-0042`). Sin URL, sin datos del lote.
3. **No se guarda la imagen** en la base de datos ni en disco. El código del lote es el dato; el QR se redibuja cuando haga falta.
4. Flujo: se crea el lote en el panel → el panel dibuja e imprime la etiqueta (`window.print()` con `@media print`) → se pega en el lote físico → el encargado la escanea con la app → la app consulta la API por código con su token.

## Consecuencias

- No hay endpoint ni paquete PHP para QR. Se quita Simple QrCode del stack.
- **El código de un lote es inmutable** una vez impreso: cambiarlo dejaría etiquetas físicas huérfanas. Si el formato del código cambia, los lotes existentes conservan el suyo.
- La API necesita una ruta de consulta por código (no solo por id), p. ej. `GET /api/lotes/{codigo}`, protegida con `auth:sanctum` y la ability `lotes:read`.
- Reimprimir una etiqueta es abrir el lote en el panel y volver a imprimir; no requiere nada en el backend.
- Si algún día las etiquetas se generan como PDF desde Laravel (lotes masivos, impresora industrial), se reconsidera `bacon/bacon-qr-code` 3 en el backend. Ese día se escribe otro ADR.

## Alternativas descartadas

- **Simple QrCode en Laravel**: conflicto de dependencias sin solución a la vista; imagen generada en el servidor sin un lugar claro donde guardarla.
- **QR con una URL** (`https://panel.../lotes/LOT-2026-0042`): ataría las etiquetas físicas al dominio; si cambia, todas quedan inservibles. La app no necesita una URL: ya sabe a qué API hablar.
- **QR con los datos del lote embebidos**: la información quedaría desactualizada en cuanto el lote avance de área, y crecería el tamaño del QR sin beneficio.
