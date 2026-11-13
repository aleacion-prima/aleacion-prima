import { flushPromises, mount } from '@vue/test-utils';
import { Quasar } from 'quasar';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import NuevaContrasenaPage from './NuevaContrasenaPage.vue';

const restablecer = vi.fn<(datos: Record<string, string>) => Promise<string>>();
const push = vi.fn();

vi.mock('@/stores/sesion', () => ({ useSesionStore: () => ({ restablecer }) }));
vi.mock('vue-router', () => ({
  useRouter: () => ({ push }),
  useRoute: () => ({ query: { token: 'abc', email: 'juan@aleacionprima.test' } }),
}));

const montar = () =>
  mount(NuevaContrasenaPage, {
    global: {
      plugins: [Quasar],
      stubs: { QPage: { template: '<div><slot /></div>' }, RouterLink: true },
    },
  });

const enviar = async (
  wrapper: ReturnType<typeof montar>,
  clave: string,
  confirmacion: string,
): Promise<void> => {
  const [campoClave, campoConfirmacion] = wrapper.findAll('input[type="password"]');
  await campoClave?.setValue(clave);
  await campoConfirmacion?.setValue(confirmacion);
  await wrapper.find('form').trigger('submit');
  await flushPromises();
};

beforeEach(() => {
  restablecer.mockReset();
  push.mockReset();
});

describe('NuevaContrasenaPage', () => {
  it('no llama a la API si la contraseña no cumple la política', async () => {
    const wrapper = montar();

    await enviar(wrapper, 'aleacion2026', 'aleacion2026');

    expect(wrapper.text()).toContain('Incluye mayúsculas y minúsculas');
    expect(restablecer).not.toHaveBeenCalled();
  });

  it('no llama a la API si la confirmación no coincide', async () => {
    const wrapper = montar();

    await enviar(wrapper, 'Aleacion2026', 'Aleacion2025');

    expect(wrapper.text()).toContain('Las contraseñas no coinciden');
    expect(restablecer).not.toHaveBeenCalled();
  });

  it('guarda la contraseña válida y vuelve al login', async () => {
    restablecer.mockResolvedValue('Contraseña restablecida.');
    const wrapper = montar();

    await enviar(wrapper, 'Aleacion2026', 'Aleacion2026');

    expect(restablecer).toHaveBeenCalledWith({
      token: 'abc',
      email: 'juan@aleacionprima.test',
      password: 'Aleacion2026',
      password_confirmation: 'Aleacion2026',
    });
    expect(push).toHaveBeenCalledWith({ path: '/login', query: { restablecida: '1' } });
  });
});
