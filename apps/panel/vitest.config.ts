import { fileURLToPath } from 'node:url';
import vue from '@vitejs/plugin-vue';
import { quasar, transformAssetUrls } from '@quasar/vite-plugin';
import { defineConfig } from 'vitest/config';

const fromRoot = (path: string): string => fileURLToPath(new URL(path, import.meta.url));

export default defineConfig({
  plugins: [
    vue({ template: { transformAssetUrls } }),
    quasar({ sassVariables: fromRoot('./src/css/quasar.variables.scss') }),
  ],
  resolve: {
    alias: {
      '@': fromRoot('./src'),
      src: fromRoot('./src'),
      components: fromRoot('./src/components'),
      layouts: fromRoot('./src/layouts'),
      pages: fromRoot('./src/pages'),
      stores: fromRoot('./src/stores'),
      boot: fromRoot('./src/boot'),
      assets: fromRoot('./src/assets'),
    },
  },
  test: {
    environment: 'jsdom',
    include: ['src/**/*.spec.ts'],
  },
});
