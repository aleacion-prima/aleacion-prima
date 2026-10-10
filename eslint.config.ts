import baseConfig from '@aleacion-prima/config/eslint';
import { defineConfig } from 'eslint/config';

export default defineConfig([{ ignores: ['apps/api/**'] }, baseConfig(import.meta.dirname)]);
