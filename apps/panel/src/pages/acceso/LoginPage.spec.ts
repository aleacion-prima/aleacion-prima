import { flushPromises, mount } from '@vue/test-utils';
import { Quasar } from 'quasar';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import LoginPage from './LoginPage.vue';

const iniciar = vi.fn<(email: string, password: string) => Promise<void>>();
const push = vi.fn();

vi.mock('@/stores/sesion', () => ({ useSesionStore: () => ({ iniciar }) }));
vi.mock('vue-router', () => ({
  useRouter: () => ({ push }),
  useRoute: () => ({ query: {} }),
}));

const montar = () =>
  mount(LoginPage, {
    global: {
      plugins: [Quasar],
      stubs: { QPage: { template: '<div><slot /></div>' }, RouterLink: true },
    },
  });

const enviar = async (
  wrapper: ReturnType<typeof montar>,
  email: string,
  password: string,
): Promise<void> => {
  await wrapper.find('input[type="email"]').setValue(email);
  await wrapper.find('input[type="password"]').setValue(password);
  await wrapper.find('form').trigger('submit');
  await flushPromises();
};

beforeEach(() => {
  iniciar.mockReset();
  push.mockReset();
});

describe('LoginPage', () => {
  it('no llama a la API con un correo de formato inválido', async () => {
    const wrapper = montar();

    await enviar(wrapper, 'juan.aleacionprima.test', 'password');

    expect(wrapper.text()).toContain('Escribe un correo válido');
    expect(iniciar).not.toHaveBeenCalled();
  });

  it('no aplica la política de contraseña al iniciar sesión', async () => {
    iniciar.mockResolvedValue();
    const wrapper = montar();

    await enviar(wrapper, 'juan@aleacionprima.test', 'password');

    expect(iniciar).toHaveBeenCalledWith('juan@aleacionprima.test', 'password');
    expect(push).toHaveBeenCalledWith('/');
  });

  it('dice claramente cuando no hay conexión con el servidor', async () => {
    iniciar.mockRejectedValue(new Error('No hay conexión con el servidor'));
    const wrapper = montar();

    await enviar(wrapper, 'juan@aleacionprima.test', 'password');

    expect(wrapper.text()).toContain('No hay conexión con el servidor');
    expect(push).not.toHaveBeenCalled();
  });
});
