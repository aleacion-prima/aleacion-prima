import { flushPromises, mount } from '@vue/test-utils';
import { QBtn, Quasar } from 'quasar';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import InicioMovilPage from './InicioMovilPage.vue';

const push = vi.fn();

vi.mock('vue-router', () => ({ useRouter: () => ({ push }) }));

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
});
