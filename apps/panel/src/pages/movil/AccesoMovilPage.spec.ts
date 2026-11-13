import { flushPromises, mount } from '@vue/test-utils';
import { Quasar } from 'quasar';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import AccesoMovilPage from './AccesoMovilPage.vue';

const iniciar = vi.fn<(email: string, password: string) => Promise<void>>();
const replace = vi.fn();

vi.mock('@/stores/sesion', () => ({ useSesionStore: () => ({ iniciar }) }));
vi.mock('vue-router', () => ({ useRouter: () => ({ replace }) }));

const montar = () =>
  mount(AccesoMovilPage, {
    global: { plugins: [Quasar], stubs: { QPage: { template: '<div><slot /></div>' } } },
  });

const llenarYEnviar = async (wrapper: ReturnType<typeof montar>): Promise<void> => {
  await wrapper.find('input[type="email"]').setValue('juan@aleacionprima.test');
  await wrapper.find('input[type="password"]').setValue('password');
  await wrapper.find('form').trigger('submit');
  await flushPromises();
};

beforeEach(() => {
  iniciar.mockReset();
  replace.mockReset();
});

describe('AccesoMovilPage', () => {
  it('inicia sesión y entra a la app', async () => {
    iniciar.mockResolvedValue();
    const wrapper = montar();

    await llenarYEnviar(wrapper);

    expect(iniciar).toHaveBeenCalledWith('juan@aleacionprima.test', 'password');
    expect(replace).toHaveBeenCalledWith('/');
  });

  it('muestra el error de la API y se queda en la pantalla', async () => {
    iniciar.mockRejectedValue(new Error('Correo o contraseña incorrectos.'));
    const wrapper = montar();

    await llenarYEnviar(wrapper);

    expect(wrapper.text()).toContain('Correo o contraseña incorrectos.');
    expect(replace).not.toHaveBeenCalled();
  });

  it('no envía un correo con formato inválido', async () => {
    const wrapper = montar();

    await wrapper.find('input[type="email"]').setValue('juan@aleacion');
    await wrapper.find('input[type="password"]').setValue('password');
    await wrapper.find('form').trigger('submit');
    await flushPromises();

    expect(wrapper.text()).toContain('Escribe un correo válido');
    expect(iniciar).not.toHaveBeenCalled();
  });

  it('no envía el formulario vacío', async () => {
    const wrapper = montar();

    await wrapper.find('form').trigger('submit');
    await flushPromises();

    expect(iniciar).not.toHaveBeenCalled();
  });
});
