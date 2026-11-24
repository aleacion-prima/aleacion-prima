import { expect, test } from '@playwright/test';

test('el inicio presenta la empresa y lleva al catálogo', async ({ page }) => {
  await page.goto('/');

  await expect(page.getByRole('heading', { level: 1, name: /lote por lote/ })).toBeVisible();
  await expect(page.getByRole('heading', { name: 'Lo que fundimos' })).toBeVisible();
  await expect(page.getByRole('heading', { name: 'La planta' })).toBeVisible();
  await expect(page.getByText('Welcome to Astro')).toHaveCount(0);

  await page.getByRole('link', { name: 'Ver catálogo' }).first().click();
  await expect(page).toHaveURL(/\/catalogo$/);
});

test('los destacados salen de la API, como máximo tres y sin precios', async ({ page }) => {
  await page.goto('/');

  const productos = page.locator('.destacados');
  await productos.scrollIntoViewIfNeeded();
  await expect(productos.getByText('Cargando productos…')).toHaveCount(0);
  const tarjetas = productos.locator('article');
  expect(await tarjetas.count()).toBeLessThanOrEqual(3);
  await expect(productos).not.toContainText('$');
});
