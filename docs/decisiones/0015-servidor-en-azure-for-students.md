# ADR-0015: Servidor en una VM de Azure for Students con dominio de sslip.io

- **Estado:** aceptada
- **Alcance:** `infra`, `docs`

## Contexto

ADR-0007 y ADR-0013 daban por hecho un droplet de DigitalOcean pagado con los créditos del GitHub Student Developer Pack, pero DigitalOcean salió del Pack antes del despliegue de prueba (T-16). El reemplazo tenía que cumplir cuatro condiciones:

1. **Siempre encendido.** Reverb (WebSockets) y el worker de la cola son procesos permanentes; un plan gratuito que se duerme tras unos minutos sin tráfico corta el tiempo real y deja trabajos sin procesar.
2. **Docker Compose tal cual.** El pipeline (`deploy.yml`) y `compose.prod.yaml` ya existían; cambiar de modelo de despliegue era rehacer la infraestructura.
3. **Sin tarjeta de crédito.** El equipo no tiene una, y varias opciones gratuitas la piden para verificar identidad.
4. **Panel y API bajo el mismo dominio registrable.** La sesión de Sanctum usa una cookie compartida entre `panel.` y `api.` (ADR-0001). Los subdominios gratuitos de varias plataformas (`onrender.com`, `herokuapp.com`, `pages.dev`…) están en la Public Suffix List, y ahí el navegador no deja compartir la cookie.

La verificación de Azure for Students con el correo escolar falla: el dominio de la universidad no está en la lista de Microsoft.

## Decisión

1. **Azure for Students**, verificado con la cuenta de GitHub en lugar del correo escolar. Da 100 USD de crédito por año, sin tarjeta, y se renueva mientras se siga siendo estudiante.
2. **Una VM Ubuntu 24.04 LTS**, tamaño `Standard_B2ats_v2` (2 vCPU, 1 GiB), que entra en las 750 horas mensuales gratuitas de la suscripción; disco SSD Premium de 64 GB y **2 GiB de swap**. Docker Engine y Compose instalados con el script oficial.
3. **Región North Central US (Chicago).** La política de la suscripción solo permite cinco regiones (`francecentral`, `belgiumcentral`, `norwayeast`, `northcentralus`, `brazilsouth`) y de ellas solo `northcentralus` y `belgiumcentral` tienen ese tamaño disponible. Chicago es la más cercana a los usuarios y a las bases de Neon y Atlas, ambas en `us-east-1`.
4. **Dominio de sslip.io:** `20-80-56-138.sslip.io` para el sitio, `panel.` y `api.` como subdominios. sslip.io resuelve cualquier nombre con la IP que lleva escrita, no está en la Public Suffix List y Caddy obtiene los certificados TLS igual que con un dominio propio. La IP pública es estática.
5. **El mismo pipeline de ADR-0007.** `deploy.yml` entra por SSH con una llave exclusiva para el despliegue (no la personal de nadie) y deja los archivos en `/opt/aleacion-prima`.
6. **El despliegue de prueba usa las bases de integración** de T0.6 (`docs/bases-de-datos.md`).

## Consecuencias

- **1 GiB de RAM es justo** para la API, Reverb, la cola y Caddy. El swap evita que el sistema mate procesos por falta de memoria, y en el servidor nunca se compila nada: las imágenes se construyen en GitHub Actions y la VM solo las descarga.
- **El crédito vence el 25 de octubre de 2027.** El consumo se revisa en el portal, en _Education_. Si la VM sale de las horas gratuitas, el crédito alcanza de sobra para el cuatrimestre.
- **Las regiones que muestra el formulario del portal no son las que permite la política**: el portal ofrece regiones que luego fallan con `RequestDisallowedByAzure`. La VM se creó con la CLI desde Cloud Shell, y las regiones válidas se consultan con `az policy assignment list --disable-scope-strict-match`.
- **El dominio depende de la IP.** Si la IP cambia, hay que actualizar la variable `DOMAIN` y el secret `API_ENV`, y volver a desplegar, porque las URLs públicas se compilan dentro de la imagen de Caddy.
- **sslip.io es un servicio de terceros** y comparte con todos sus usuarios los límites de emisión de Let's Encrypt. Si se alcanzan, Caddy recurre a ZeroSSL. Pasar a un dominio propio es cambiar `DOMAIN` y `API_ENV`, apuntar el DNS a la IP y desplegar.
- El puerto 22 queda abierto a internet, pero solo se entra con llave SSH; no hay contraseña que adivinar.

## Alternativas descartadas

- **DigitalOcean:** salió del GitHub Student Developer Pack.
- **Oracle Cloud Always Free:** pide tarjeta de crédito para verificar la cuenta.
- **Heroku con el crédito del Pack:** también pide tarjeta, y `herokuapp.com` está en la Public Suffix List.
- **Render y Koyeb en plan gratuito:** se duermen sin tráfico y sus subdominios están en la Public Suffix List.
- **Hugging Face Spaces:** los Spaces con Docker ahora requieren plan de pago y bloquean la salida a los puertos de PostgreSQL y MongoDB.
- **Fly.io y Railway:** solo periodo de prueba o tarjeta obligatoria.
- **VPS de pago con OXXO o SPEI (Hostinger):** cobra 24 meses por adelantado.
