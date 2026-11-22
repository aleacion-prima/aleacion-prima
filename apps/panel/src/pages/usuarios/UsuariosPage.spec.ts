import { flushPromises, mount } from '@vue/test-utils';
import { QBtn, Quasar } from 'quasar';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import UsuariosPage from './UsuariosPage.vue';

const validacion = vi.hoisted(() => ({
  message: 'La contraseña debe contener al menos una letra mayúscula y una minúscula.',
  errors: {
    password: ['La contraseña debe contener al menos una letra mayúscula y una minúscula.'],
  },
}));

const respuesta = (data: unknown, error: unknown = null) => ({
  data: { value: data },
  error: { value: error },
});

const useApi = vi.fn((url: string) => {
  if (url === '/usuarios') {
    return {
      post: () => ({ json: () => Promise.resolve(respuesta(validacion, new Error('422'))) }),
    };
  }

  return { json: () => Promise.resolve(respuesta([])) };
});

vi.mock('@/services/api', () => ({ useApi: (url: string) => useApi(url) }));
vi.mock('@/stores/sesion', () => ({
  useSesionStore: () => ({ usuario: { id: 1 } }),
}));

const montar = async () => {
  const wrapper = mount(UsuariosPage, {
    global: {
      plugins: [Quasar],
      stubs: {
        QPage: { template: '<div><slot /></div>' },
        QDialog: { template: '<div><slot /></div>' },
        QTabs: { template: '<div><slot /></div>' },
        QTab: { template: '<div />' },
      },
    },
  });
  await flushPromises();
  return wrapper;
};

const botonConEtiqueta = (wrapper: Awaited<ReturnType<typeof montar>>, etiqueta: string) => {
  const boton = wrapper
    .findAllComponents(QBtn)
    .find((actual) => actual.props('label') === etiqueta);
  if (!boton) throw new Error(`No se encontró el botón "${etiqueta}"`);
  return boton;
};

beforeEach(() => {
  useApi.mockClear();
});

describe('UsuariosPage', () => {
  it('muestra debajo del campo el mensaje de validación que devuelve la API', async () => {
    const wrapper = await montar();

    await botonConEtiqueta(wrapper, 'Nuevo usuario').trigger('click');
    const campos = wrapper.findAll('input');
    await campos[0]?.setValue('Ana Ramírez');
    await campos[1]?.setValue('ana@aleacionprima.test');
    await campos[2]?.setValue('sinmayuscula');

    await botonConEtiqueta(wrapper, 'Guardar').trigger('click');
    await flushPromises();

    expect(wrapper.text()).toContain(
      'La contraseña debe contener al menos una letra mayúscula y una minúscula.',
    );
  });
});
