import { expect, test } from '@playwright/test';

test('la página de inicio carga', async ({ page }) => {
  await page.goto('/');
  await expect(page).toHaveTitle(/.+/);
});
