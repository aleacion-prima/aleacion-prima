import js from '@eslint/js';
import tseslint from 'typescript-eslint';
import prettier from 'eslint-config-prettier';
import { defineConfig } from 'eslint/config';

export default function baseConfig(tsconfigRootDir: string) {
  return defineConfig([
    { ignores: ['**/dist/**', '**/.quasar/**', '**/.astro/**', '**/src-capacitor/**'] },
    js.configs.recommended,
    tseslint.configs.recommended,
    {
      languageOptions: {
        parserOptions: { tsconfigRootDir },
      },
    },
    prettier,
  ]);
}
