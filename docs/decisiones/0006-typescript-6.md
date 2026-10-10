# ADR-0006: TypeScript 6 en todo el monorepo mientras Astro no soporte 7

- **Estado:** aceptada (revisar cuando `astro check` soporte TypeScript 7)
- **Alcance:** `panel`, `web`, `types`, `config`, `scripts`, `e2e`

## Contexto

Todo el código de frontend, paquetes, scripts y configuración está en TypeScript, con una configuración base compartida en `@aleacion-prima/config`. Al instalar, `astro check` falló con `does not currently support TypeScript 7.0`. Tener versiones distintas por paquete rompería la configuración compartida y daría errores de tipos diferentes según desde dónde se corra `typecheck`.

## Decisión

- **TypeScript 6 en todo el monorepo**, con la versión fijada en la raíz y en cada paquete que lo declare.
- Configuración base: `strict`, `noUncheckedIndexedAccess`, `verbatimModuleSyntax`, `moduleResolution: Bundler`, `noEmit`. El panel y el sitio la extienden junto con la de su framework.
- Se migra a TypeScript 7 cuando Astro lo soporte, en un solo PR para todo el monorepo.

## Consecuencias

- En TS 6, `types` vale `[]` por defecto: los archivos que usan APIs de Node necesitan `"types": ["node"]` en su `tsconfig` y estar en su `include`.
- Los scripts de `scripts/` los ejecuta Node 24 quitando los tipos, sin compilar: solo se permite sintaxis borrable (nada de `enum`, `namespace` ni propiedades en el constructor).
- `packages/types` no se compila: su `exports` apunta a `src/index.ts` y se importa con `import type`. Solo tipos, sin constantes.
- Al actualizar dependencias, revisar que ninguna arrastre TypeScript 7 como peer dependency.

## Alternativas descartadas

- **TypeScript 7 en todo**: `astro check` no funciona y el sitio se queda sin verificación de tipos.
- **Versiones mixtas** (7 en el panel, 6 en el sitio): dos compiladores, errores inconsistentes y una configuración base que ya no sería compartida.
