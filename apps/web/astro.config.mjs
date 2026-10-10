// @ts-check
import vue from '@astrojs/vue';
import { defineConfig, envField } from 'astro/config';

// https://astro.build/config
export default defineConfig({
  integrations: [vue()],
  env: {
    schema: {
      PUBLIC_API_URL: envField.string({
        context: 'client',
        access: 'public',
        default: 'http://localhost:8000',
      }),
      PUBLIC_REVERB_HOST: envField.string({
        context: 'client',
        access: 'public',
        default: 'localhost',
      }),
      PUBLIC_REVERB_PORT: envField.number({ context: 'client', access: 'public', default: 8080 }),
      PUBLIC_REVERB_SCHEME: envField.enum({
        context: 'client',
        access: 'public',
        values: ['http', 'https'],
        default: 'http',
      }),
      PUBLIC_REVERB_KEY: envField.string({
        context: 'client',
        access: 'public',
        default: 'aleacion-local',
      }),
    },
  },
});
