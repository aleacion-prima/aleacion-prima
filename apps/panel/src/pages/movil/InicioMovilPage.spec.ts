import { flushPromises, mount } from '@vue/test-utils';
import { QBtn, QItem, Quasar } from 'quasar';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import type { LoteEscaneado } from '@/services/escaneados';
import InicioMovilPage from './InicioMovilPage.vue';

const push = vi.fn();

vi.mock('vue-router', () => ({ useRouter: () => ({ push }) }));

const guardados = vi.hoisted((): { lista: LoteEscaneado[] } => ({ lista: [] }));
vi.mock('@/services/escaneados', () => ({
  leerEscaneados: () => Promise.resolve(guardados.lista),
}));

const montar = () =>
  mount(InicioMovilPage, {
    global: {
      plugins: [Quasar],
      stubs: { QPage: { template: '<div><slot /></div>' }, RouterLink: true },
    },
  });

const buscar = async (wrapper: ReturnType<typeof montar>, codigo: string): Promise<void> => {
  await wrapper.find('input').setValue(codigo);
  await wrapper.find('form').trigger('submit');
  await flushPromises();
};

beforeEach(() => {
  push.mockReset();
  guardados.lista = [];
});

describe('InicioMovilPage', () => {
  it('lleva a escanear', () => {
    const boton = montar()
      .findAllComponents(QBtn)
      .find((b) => b.attributes('aria-label') === 'Escanear etiqueta');

    expect(boton?.props('to')).toBe('/escanear');
  });

  it('busca un código escrito a mano', async () => {
    const wrapper = montar();

    await buscar(wrapper, ' lot-20260928-01 ');

    expect(push).toHaveBeenCalledWith('/lotes/LOT-20260928-01');
  });

  it('no busca un código con otro formato', async () => {
    const wrapper = montar();

    await buscar(wrapper, 'CAJ-TRA-N');

    expect(push).not.toHaveBeenCalled();
    expect(wrapper.text()).toContain('Usa el formato LOT-AAAAMMDD-NN');
  });

  it('muestra los últimos lotes consultados y abre el que se toca', async () => {
    guardados.lista = [
      {
        codigo: 'LOT-20260928-01',
        producto: 'Caja de transmisión',
        estado: 'en_proceso',
        consultado_en: '2026-09-29T15:40:00Z',
      },
      {
        codigo: 'LOT-20260924-01',
        producto: 'Disco de freno',
        estado: 'terminado',
        consultado_en: '2026-09-25T19:20:00Z',
      },
    ];
    const wrapper = montar();
    await flushPromises();

    expect(wrapper.text()).toContain('Últimos escaneados');
    const items = wrapper.findAllComponents(QItem);
    expect(items).toHaveLength(2);
    expect(items[0]?.text()).toContain('Caja de transmisión');
    expect(items[0]?.props('to')).toBe('/lotes/LOT-20260928-01');
    expect(items[1]?.text()).toContain('Terminado');
  });

  it('sin lotes consultados no muestra la sección', async () => {
    const wrapper = montar();
    await flushPromises();

    expect(wrapper.text()).not.toContain('Últimos escaneados');
  });
});
