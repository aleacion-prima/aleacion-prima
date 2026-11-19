import { flushPromises, mount } from '@vue/test-utils';
import { Quasar } from 'quasar';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import EscanearPage from './EscanearPage.vue';

const escaner = vi.hoisted(() => ({
  permisoCamara: vi.fn<() => Promise<boolean>>(),
  iniciarEscaneo: vi.fn<(alLeer: (texto: string) => void) => Promise<void>>(),
  detenerEscaneo: vi.fn<() => Promise<void>>(),
}));
const replace = vi.fn();
const notify = vi.fn();

vi.mock('@/services/escaner', () => escaner);
vi.mock('vue-router', () => ({ useRouter: () => ({ replace }) }));
vi.mock('quasar', async (original) => ({
  ...(await original<Record<string, unknown>>()),
  useQuasar: () => ({ notify }),
}));

let leer: (texto: string) => void = () => undefined;

const montar = async () => {
  const wrapper = mount(EscanearPage, {
    global: { plugins: [Quasar], stubs: { QPage: { template: '<div><slot /></div>' } } },
  });
  await flushPromises();
  return wrapper;
};

beforeEach(() => {
  vi.clearAllMocks();
  escaner.permisoCamara.mockResolvedValue(true);
  escaner.detenerEscaneo.mockResolvedValue();
  escaner.iniciarEscaneo.mockImplementation((alLeer) => {
    leer = alLeer;
    return Promise.resolve();
  });
});

afterEach(() => {
  document.body.classList.remove('escaneando');
});

describe('EscanearPage', () => {
  it('con permiso abre la cámara y vuelve transparente la página', async () => {
    await montar();

    expect(escaner.iniciarEscaneo).toHaveBeenCalled();
    expect(document.body.classList.contains('escaneando')).toBe(true);
  });

  it('con un código de lote válido detiene la cámara y abre su consulta', async () => {
    await montar();

    leer('lot-20260928-01');
    await flushPromises();

    expect(escaner.detenerEscaneo).toHaveBeenCalled();
    expect(replace).toHaveBeenCalledWith('/lotes/LOT-20260928-01');
    expect(document.body.classList.contains('escaneando')).toBe(false);
  });

  it('solo navega una vez aunque la cámara lea el código varias veces', async () => {
    await montar();

    leer('LOT-20260928-01');
    leer('LOT-20260928-01');
    await flushPromises();

    expect(replace).toHaveBeenCalledTimes(1);
  });

  it('un QR ajeno avisa una sola vez y sigue escaneando', async () => {
    await montar();

    leer('https://ejemplo.com');
    leer('https://ejemplo.com');
    await flushPromises();

    expect(notify).toHaveBeenCalledTimes(1);
    expect(notify).toHaveBeenCalledWith(
      expect.objectContaining({ message: 'Ese QR no es de un lote' }),
    );
    expect(replace).not.toHaveBeenCalled();
    expect(escaner.detenerEscaneo).not.toHaveBeenCalled();
  });

  it('sin permiso no abre la cámara y ofrece escribir el código', async () => {
    escaner.permisoCamara.mockResolvedValue(false);
    const wrapper = await montar();

    expect(escaner.iniciarEscaneo).not.toHaveBeenCalled();
    expect(wrapper.text()).toContain('Sin permiso para usar la cámara');

    await wrapper
      .findAll('button')
      .find((b) => b.text().includes('Escribir el código'))
      ?.trigger('click');
    await flushPromises();

    expect(replace).toHaveBeenCalledWith('/');
  });

  it('si la cámara falla limpia la página y ofrece escribir el código', async () => {
    escaner.iniciarEscaneo.mockRejectedValue(new Error('not implemented'));
    const wrapper = await montar();

    expect(wrapper.text()).toContain('No se pudo abrir la cámara');
    expect(document.body.classList.contains('escaneando')).toBe(false);
  });

  it('al salir de la pantalla apaga la cámara', async () => {
    const wrapper = await montar();

    wrapper.unmount();
    await flushPromises();

    expect(escaner.detenerEscaneo).toHaveBeenCalled();
    expect(document.body.classList.contains('escaneando')).toBe(false);
  });
});
