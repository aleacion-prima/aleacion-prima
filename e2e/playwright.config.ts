import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
  fullyParallel: true,
  forbidOnly: Boolean(process.env.CI),
  retries: process.env.CI ? 2 : 0,
  reporter: 'html',
  use: {
    trace: 'on-first-retry',
  },
  projects: [
    {
      name: 'web',
      testDir: './tests/web',
      use: { ...devices['Desktop Chrome'], baseURL: 'http://localhost:4321' },
    },
    {
      name: 'panel',
      testDir: './tests/panel',
      use: { ...devices['Desktop Chrome'], baseURL: 'http://localhost:9000' },
    },
  ],
});
