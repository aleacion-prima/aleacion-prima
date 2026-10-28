# Bases de datos

Cómo se conecta cada variante de base de datos del proyecto y, en concreto, las **bases de integración** (Neon y MongoDB Atlas) que usa el despliegue de prueba. El porqué de usar dos bases está en [ADR-0003](./decisiones/0003-postgres-y-mongo.md); las piezas y el despliegue, en [arquitectura.md](./arquitectura.md).

> ⚠️ **Ninguna credencial vive en el repositorio.** Las cadenas de conexión reales de integración y producción se comparten por el canal privado del equipo. Aquí solo se documenta _qué_ se creó y _cómo_ se configura, nunca con qué contraseña.

## Qué se creó

| Entorno         | PostgreSQL                                               | MongoDB                                                                |
| --------------- | -------------------------------------------------------- | ---------------------------------------------------------------------- |
| **Desarrollo**  | Docker local (`postgres:18`)                             | Docker local (`mongo:7.0`)                                             |
| **Integración** | Neon — proyecto `aleacion-prima`, región AWS `us-east-1` | Atlas — clúster `aleacion-prima` (M0 gratuito), región AWS `us-east-1` |
| **Producción**  | Neon — instancia distinta                                | Atlas — misma cuenta                                                   |
| **Tests**       | SQLite en memoria                                        | No se usa                                                              |

Las dos bases de integración están en el **plan gratuito**: Neon (PostgreSQL serverless) y Atlas M0.

## Qué variable apunta a cada base

Todo se configura en `apps/api/.env` (plantilla en [`apps/api/.env.example`](../apps/api/.env.example)). Para cambiar de entorno **solo se cambian estas variables**, sin tocar código.

### PostgreSQL

| Variable        | Desarrollo (Docker) | Integración (Neon)                     |
| --------------- | ------------------- | -------------------------------------- |
| `DB_CONNECTION` | `pgsql`             | `pgsql`                                |
| `DB_HOST`       | `postgres`          | `ep-xxxx-xxxx.us-east-1.aws.neon.tech` |
| `DB_PORT`       | `5432`              | `5432`                                 |
| `DB_DATABASE`   | `aleacion_prima`    | el que asigna Neon                     |
| `DB_USERNAME`   | `aleacion`          | el usuario de Neon                     |
| `DB_PASSWORD`   | local               | contraseña de Neon (canal privado)     |
| `DB_SSLMODE`    | `prefer`            | `require`                              |

Neon **exige TLS**, por eso en integración `DB_SSLMODE=require`. En local se queda en `prefer`.

### MongoDB

| Variable           | Desarrollo              | Integración (Atlas)                                                                            |
| ------------------ | ----------------------- | ---------------------------------------------------------------------------------------------- |
| `MONGODB_URI`      | `mongodb://mongo:27017` | `mongodb+srv://<usuario>:<clave>@aleacion-prima.xxxx.mongodb.net/?retryWrites=true&w=majority` |
| `MONGODB_DATABASE` | `aleacion_prima`        | `aleacion_prima`                                                                               |

La conexión `mongodb` de `config/database.php` lee `MONGODB_URI`.

## Cómo probar la conexión desde local

Solo sirve para verificar las credenciales de integración; no hace falta para el día a día. Apunta tu `.env` a la base de integración **de forma temporal**, prueba, y **regresa** tu `.env` a los valores locales. Nunca subas esas credenciales.

**PostgreSQL (Neon):**

```bash
pnpm artisan db:show
```

Debe mostrar la conexión al host de Neon. Si conecta, listo. La base de integración puede no tener tablas todavía: el esquema lo aplica el pipeline de despliegue, no tu máquina.

**MongoDB (Atlas)**, dentro de `pnpm artisan tinker`:

```php
DB::connection('mongodb')->getClient()->listDatabases();
```

Si devuelve la lista de bases sin lanzar una excepción, la conexión a Atlas funciona.

## Regla de oro: nunca migrar ni sembrar contra integración o producción

Migraciones y seeders **solo** contra la base local de Docker (`DB_HOST=postgres`). **Nunca** contra Neon ni Atlas desde una máquina ([ADR-0003](./decisiones/0003-postgres-y-mongo.md)). El esquema de integración y de producción lo aplica el pipeline de despliegue. Si tu `.env` apunta a Neon o Atlas, no corras `migrate`, `migrate:fresh` ni `db:seed`.
