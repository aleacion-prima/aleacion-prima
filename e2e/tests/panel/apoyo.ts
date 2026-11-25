import { expect, type Locator, type Page } from '@playwright/test';

export const ADMIN = { correo: 'admin@aleacionprima.test', password: 'password' };

export const SITIO = process.env.E2E_SITIO_URL ?? 'http://localhost:4321';

export const campo = (ambito: Page | Locator, etiqueta: string): Locator =>
  ambito.locator('.q-field', { hasText: etiqueta }).locator('input, textarea').first();

export const entrarComoAdmin = async (page: Page): Promise<void> => {
  await page.goto('/#/login');
  await campo(page, 'Correo electrónico').fill(ADMIN.correo);
  await campo(page, 'Contraseña').fill(ADMIN.password);
  await Promise.all([
    page.waitForResponse(
      (r) => r.url().includes('/login') && r.request().method() === 'POST' && r.ok(),
    ),
    page.getByRole('button', { name: 'Ingresar' }).click(),
  ]);
  await expect(page).not.toHaveURL(/#\/login/);
};
