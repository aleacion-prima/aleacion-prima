# Decisiones de diseño (ADR)

Cada archivo de esta carpeta es un **registro de decisión de arquitectura** (ADR): una página que explica una decisión técnica importante, el problema que resolvía y por qué se descartaron las alternativas. Sirven para que, cuando alguien pregunte "¿y por qué no usamos X?", la respuesta ya esté escrita.

## Índice

| ADR                                              | Decisión                                                                     |
| ------------------------------------------------ | ---------------------------------------------------------------------------- |
| [0001](./0001-sanctum-sin-jwt.md)                | Autenticación con Sanctum (cookie en el panel, tokens en la app), sin JWT    |
| [0002](./0002-un-guard-roles-y-abilities.md)     | Un solo guard `web`; los roles y las abilities del token son cosas distintas |
| [0003](./0003-postgres-y-mongo.md)               | PostgreSQL como fuente de verdad; MongoDB solo para bitácora e imprevistos   |
| [0004](./0004-qr-en-el-panel.md)                 | El QR se genera en el panel y solo contiene el código del lote               |
| [0005](./0005-reverb-canal-publico-minimo.md)    | Tiempo real con Reverb; el canal público transmite lo mínimo, nunca precios  |
| [0006](./0006-typescript-6.md)                   | TypeScript 6 en todo el monorepo mientras Astro no soporte 7                 |
| [0007](./0007-sin-staging-rollback-por-tag.md)   | Sin ambiente de staging; CI obligatorio y rollback por tag de imagen         |
| [0008](./0008-app-movil-solo-lectura.md)         | La app Android es de solo lectura y comparte código con el panel             |
| [0009](./0009-recorrido-por-lote.md)             | El recorrido se define por producto y se congela en cada lote                |
| [0010](./0010-confirmacion-de-cierre.md)         | El administrador confirma el cierre antes de inventario y publicación        |
| [0011](./0011-sin-reapertura.md)                 | Un lote terminado no se reabre; se corrige con ajustes                       |
| [0012](./0012-retornos.md)                       | Los retornos de metal vuelven al inventario y se restan del costo del lote   |
| [0013](./0013-github-como-plataforma.md)         | GitHub como plataforma del repositorio, el CI, las imágenes y el tablero     |
| [0014](./0014-github-flow-squash.md)             | GitHub Flow con una rama por tarea y Squash and merge                        |
| [0015](./0015-servidor-en-azure-for-students.md) | Servidor en una VM de Azure for Students con dominio de sslip.io             |

## Formato

```
# ADR-NNNN: Título en una línea

- **Estado:** propuesta | aceptada | reemplazada por ADR-NNNN
- **Alcance:** api, panel, web, infra... (los mismos alcances de los commits)

## Contexto        → el problema o la situación que obligó a decidir
## Decisión        → qué se decidió, en concreto
## Consecuencias   → qué obliga, qué cuesta, qué hay que tener en cuenta (incluido lo incómodo)
## Alternativas descartadas → qué más se consideró y por qué no
```

## Reglas

- **Un ADR aceptado no se edita** (salvo typos). Si la decisión cambia, se escribe uno nuevo que la reemplaza y el viejo cambia su estado a `reemplazada por ADR-NNNN`.
- Numeración consecutiva de cuatro dígitos; nombre de archivo en kebab-case sin acentos: `0009-nombre-corto.md`.
- Se escribe un ADR cuando la decisión es difícil de revertir, afecta a más de una app, o alguien del equipo la cuestionó. Para detalles menores basta un comentario en el código.
- Agrega la fila al índice de arriba y menciónalo en el PR.
