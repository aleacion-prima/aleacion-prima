import { expect, test, type Page } from '@playwright/test';
import { entrarComoAdmin, SITIO } from './apoyo';

const PRODUCTO = 'Polea industrial';

const publicar = async (page: Page, publicado: boolean): Promise<void> => {
  const interruptor = page
    .locator('.q-card', { hasText: PRODUCTO })
    .locator('.q-toggle', { hasText: 'Publicado' });
  await expect(interruptor).toBeVisible();
  if ((await interruptor.getAttribute('aria-checked')) === String(publicado)) return;
  await Promise.all([
    page.waitForResponse(
      (r) => r.url().includes('/publicacion') && r.request().method() === 'PATCH' && r.ok(),
    ),
    interruptor.click(),
  ]);
  await expect(interruptor).toHaveAttribute('aria-checked', String(publicado));
};

const tarjetaEnSitio = async (sitio: Page) => {
  await sitio.goto(`${SITIO}/catalogo`);
  await expect(sitio.getByText('Cargando productos…')).toHaveCount(0);
  return sitio.getByRole('heading', { level: 3, name: PRODUCTO });
};

test('lo que se publica en el panel aparece en el catálogo del sitio', async ({ page }) => {
  await entrarComoAdmin(page);
  await page.goto('/#/productos');
  const sitio = await page.context().newPage();

  await publicar(page, false);
  await expect(await tarjetaEnSitio(sitio)).toHaveCount(0);

  await publicar(page, true);
  await expect(await tarjetaEnSitio(sitio)).toBeVisible();
});
