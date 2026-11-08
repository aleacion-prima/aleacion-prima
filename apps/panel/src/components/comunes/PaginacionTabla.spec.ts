import { mount } from '@vue/test-utils';
import { Quasar } from 'quasar';
import { describe, expect, it } from 'vitest';
import PaginacionTabla from './PaginacionTabla.vue';

const montar = (pagina: number, total: number) =>
  mount(PaginacionTabla, {
    props: { pagina, porPagina: 10, total, sustantivo: 'lotes' },
    global: { plugins: [Quasar] },
  });

describe('PaginacionTabla', () => {
  it('dice qué rango se está viendo', () => {
    expect(montar(1, 46).text()).toContain('1–10 de 46 lotes');
    expect(montar(5, 46).text()).toContain('41–46 de 46 lotes');
  });

  it('no muestra páginas si todo cabe en una', () => {
    expect(montar(1, 7).find('.q-pagination').exists()).toBe(false);
  });

  it('no se rompe sin registros', () => {
    expect(montar(1, 0).text()).toContain('0–0 de 0 lotes');
  });
});
