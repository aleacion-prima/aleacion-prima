# ADR-0008: La app Android es de solo lectura y comparte código con el panel

- **Estado:** aceptada
- **Alcance:** `api`, `panel`

## Contexto

Los encargados de área necesitan ver el estado e historial de un lote desde la planta, escaneando su etiqueta. El anteproyecto deja fuera de alcance la captura desde la app móvil: todos los registros se hacen en el sistema web al cerrar cada área. La app forma parte del MVP y no es recortable (es sencilla y se desarrolla en paralelo), pero el riesgo RI-09 (alcance mayor a la capacidad) exige que se mantenga sencilla.

## Decisión

1. **La app es de solo lectura.** Escanea el QR → consulta el lote por código → muestra estado, área actual e historial. Nada más. No se agregan formularios "porque ya estamos ahí".
2. **Vive en el mismo proyecto que el panel** (`apps/panel`), compilada con Capacitor. Las rutas y el código exclusivo de cada modo se separan con `import.meta.env.QUASAR_MODE === 'capacitor'`: Quasar evalúa esos `if` en compilación y elimina las ramas falsas, así el código del panel administrativo no entra al APK.
3. **Autenticación con tokens de Sanctum** con ability `lotes:read` (ADR-0001, ADR-0002). El backend valida el rol y la ability; la restricción de solo lectura no depende de la UI.
4. El escaneo usa **Capacitor ML Kit Barcode Scanning** (cámara nativa).

## Consecuencias

- **Sin modo offline**: sin red en la planta no se consulta nada (limitación declarada en el anteproyecto).
- La cámara requiere contexto seguro: en producción la API va por HTTPS y la app corre en el origen `https://localhost` de Capacitor, que debe estar en `CORS_ALLOWED_ORIGINS`.
- En Android, `localhost` es el propio dispositivo: para desarrollo se pasan `PUBLIC_API_URL` y `PUBLIC_REVERB_HOST` con `10.0.2.2` (emulador) o la IP de la máquina (físico). Ver README, sección 6.
- El cliente HTTP del panel necesita una rama para el modo Capacitor que envíe `Authorization: Bearer` en lugar de la cookie.
- Los tokens se emiten al iniciar sesión desde la app (`createToken('movil', ['lotes:read'])`) y se revocan al cerrar sesión o desde el panel.
- Probar el escaneo real requiere un celular físico; la cámara del emulador es limitada.

## Alternativas descartadas

- **Permitir captura desde la app**: rompe el alcance del anteproyecto, multiplica las pantallas y los casos de error (validaciones, red intermitente en planta), y es justo lo que RI-09 quiere evitar.
- **Un proyecto móvil separado**: duplicaría cliente HTTP, tipos, tienda de sesión y estilos; Quasar existe precisamente para no hacerlo.
- **PWA en lugar de Capacitor**: evitaría Android Studio, pero el escaneo de QR con la cámara en una PWA depende del navegador y es menos fiable que ML Kit; el anteproyecto compromete Capacitor.
