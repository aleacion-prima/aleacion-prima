import baseConfig from '../../packages/config/eslint/base';
import astro from 'eslint-plugin-astro';
import pluginVue from 'eslint-plugin-vue';
import globals from 'globals';
import {
  configureVueProject,
  defineConfigWithVueTs,
  vueTsConfigs,
} from '@vue/eslint-config-typescript';
import prettierSkipFormatting from '@vue/eslint-config-prettier/skip-formatting';

configureVueProject({ rootDir: import.meta.dirname });

export default defineConfigWithVueTs(
  baseConfig(import.meta.dirname),
  astro.configs.recommended,

  // Los presets de Vue (parser + reglas con info de tipos) solo aplican a
  // .ts/.vue: si no se limitan con "files", tambien intentan correr sobre
  // los .astro y truenan porque esos no tienen info de tipos generada.
  {
    files: ['**/*.ts', '**/*.vue'],
    // https://eslint.vuejs.org
    // https://github.com/vuejs/eslint-config-typescript
    extends: [pluginVue.configs['flat/essential'], vueTsConfigs.recommendedTypeChecked],
    languageOptions: {
      globals: {
        ...globals.browser,
        ...globals.node, // config de Astro y modulos que corren en el server
      },
    },
    rules: {
      '@typescript-eslint/consistent-type-imports': ['error', { prefer: 'type-imports' }],
    },
  },

  prettierSkipFormatting,
);
