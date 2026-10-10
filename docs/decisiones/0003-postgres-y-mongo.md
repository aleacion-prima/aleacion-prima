# ADR-0003: PostgreSQL como fuente de verdad; MongoDB solo para bitácora e imprevistos

- **Estado:** aceptada
- **Alcance:** `api`, `infra`

## Contexto

El anteproyecto define dos bases de datos: PostgreSQL para los datos críticos (lotes, inventario, costos, ventas) y MongoDB para bitácora de eventos e imprevistos, que son registros de alta frecuencia y estructura variable. El riesgo RI-10 del análisis de riesgos advierte lo que puede salir mal: imprevistos o entradas de bitácora huérfanas (sin su lote) o al revés, y con ello trazabilidad incompleta.

## Decisión

1. **PostgreSQL es la fuente de verdad.** Todo lo que tenga integridad referencial o afecte dinero (lotes, áreas, movimientos, insumos, existencias, pedidos, clientes, usuarios, roles) vive en Postgres. Es la conexión por defecto (`DB_CONNECTION=pgsql`).
2. **MongoDB guarda solo dos cosas:** la bitácora de eventos y los imprevistos no estructurados. Sus documentos siempre llevan el `lote_id` (y `area_id` cuando aplique) de Postgres como referencia.
3. **Se escribe en Mongo solo después de confirmar la transacción de Postgres.** Los eventos y listeners que escriben en Mongo se marcan con `$afterCommit = true` (o se usa `DB::afterCommit()`), de modo que si la transacción principal se revierte, nunca queda un documento huérfano.
4. Los modelos de Mongo extienden `MongoDB\Laravel\Eloquent\Model` con `protected $connection = 'mongodb';`. Ningún modelo de Postgres apunta a Mongo.
5. Entornos:

   | Entorno      | PostgreSQL                       | MongoDB                          |
   | ------------ | -------------------------------- | -------------------------------- |
   | Desarrollo   | Docker local (`postgres:18`)     | Docker local (`mongo:7.0`)       |
   | Integración  | Neon (proyecto "Aleacion Prima") | MongoDB Atlas                    |
   | Producción   | Neon (instancia distinta)        | MongoDB Atlas                    |
   | Tests (Pest) | SQLite en memoria                | Ninguno (`MONGODB_URI` inválida) |

## Consecuencias

- **No hay transacciones entre las dos bases.** Un documento de Mongo sin lote es un bug; un lote sin su entrada de bitácora se tolera y se reintenta (las escrituras a Mongo pueden ir por cola).
- Los tests no tocan Mongo: la `MONGODB_URI` de `phpunit.xml` es inválida a propósito para que cualquier test que la use falle en vez de escribir en Atlas. Cómo probar la bitácora (servicio `mongo` en el CI o un fake tras una interfaz) se decide al escribir el primer modelo.
- Cada quien tiene su BD local; migraciones y seeders **nunca** se corren contra Neon o Atlas desde una máquina.
- Mongo 7 en local y probablemente 8 en Atlas (Mongo 8 no arranca con kernels recientes). Diferencia conocida y aceptable mientras no se usen funciones nuevas de la 8.
- Las consultas de reportes que crucen ambas fuentes (p. ej. "imprevistos por área en el mes") se resuelven en la aplicación: se consulta Mongo por `lote_id` y se une con Postgres en PHP, no al revés.

## Alternativas descartadas

- **Solo PostgreSQL con columnas JSONB para bitácora e imprevistos.** Técnicamente bastaría y evitaría RI-10 por completo. Se descartó porque el anteproyecto compromete MongoDB y el equipo quiere la experiencia con ella. A cambio, el alcance de Mongo se mantiene pequeño (dos colecciones, siempre referenciadas por id) para que migrar a JSONB sea barato si Mongo estorba.
- **MongoDB para todo.** Sin integridad referencial ni transacciones sencillas para inventario, costos y ventas, justo donde más se necesitan.
- **Escribir en Mongo dentro de la transacción de Postgres.** Es lo que produce los huérfanos de RI-10 cuando la transacción se revierte.
