import { flushPromises, mount } from '@vue/test-utils';
import { QBtn, Quasar } from 'quasar';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import type { Lote, LoteParada } from '@aleacion-prima/types';
import LoteMovilPage from './LoteMovilPage.vue';

const respuesta = vi.hoisted(
  (): { data: Lote | null; error: Error | null; statusCode: number | null } => ({
    data: null,
    error: null,
    statusCode: null,
  }),
);
const useApi = vi.fn((url: string) => ({
  json: () =>
    Promise.resolve({
      url,
      data: { value: respuesta.data },
      error: { value: respuesta.error },
      statusCode: { value: respuesta.statusCode },
    }),
}));

vi.mock('@/services/api', () => ({ useApi: (url: string) => useApi(url) }));
const recordarEscaneado = vi.fn();
vi.mock('@/services/escaneados', () => ({
  recordarEscaneado: (lote: unknown) => recordarEscaneado(lote),
}));
vi.mock('vue-router', () => ({ useRoute: () => ({ params: { codigo: 'LOT-20261102-01' } }) }));

const parada = (orden: number, nombre: string, cambios: Partial<LoteParada>): LoteParada => ({
  id: orden,
  orden,
  area: { id: orden, codigo: nombre.slice(0, 3).toUpperCase(), nombre },
  piezas_recibidas: null,
  piezas_entregadas: null,
  merma_piezas: null,
  merma_material: null,
  retorno_kg: null,
  discrepancia: null,
  entrada_ocurrido_en: null,
  entrada_registrado_en: null,
  entrada_tardia: false,
  salida_ocurrido_en: null,
  salida_registrado_en: null,
  salida_tardia: false,
  admite_retorno: false,
  puede_capturar: true,
  ...cambios,
});

const lote: Lote = {
  id: 1,
  codigo: 'LOT-20261102-01',
  estado: 'en_proceso',
  cantidad_objetivo: 20,
  piezas_terminadas: null,
  producto: { id: 1, codigo: 'DIS-FRE-G', nombre: 'Disco de freno' },
  area_actual: { id: 2, codigo: 'FUS', nombre: 'Fusión' },
  paradas: [
    parada(1, 'Moldeo', {
      entrada_ocurrido_en: '2026-11-02T15:00:00Z',
      salida_ocurrido_en: '2026-11-02T16:00:00Z',
      piezas_entregadas: 20,
    }),
    parada(2, 'Fusión', { entrada_ocurrido_en: '2026-11-02T16:00:00Z', piezas_recibidas: 20 }),
    parada(3, 'Colada', {}),
  ],
  confirmado_en: null,
  cancelado_en: null,
  motivo_cancelacion: null,
  admite_devolucion: false,
  created_at: '2026-11-02T09:00:00Z',
};

const montar = async () => {
  const wrapper = mount(LoteMovilPage, {
    global: {
      plugins: [Quasar],
      stubs: { QPage: { template: '<div><slot /></div>' } },
    },
  });
  await flushPromises();
  return wrapper;
};

beforeEach(() => {
  useApi.mockClear();
  recordarEscaneado.mockClear();
  respuesta.data = null;
  respuesta.error = null;
  respuesta.statusCode = null;
});

describe('LoteMovilPage', () => {
  it('pide el lote escaneado y muestra su estado, área actual y recorrido', async () => {
    respuesta.data = lote;
    respuesta.statusCode = 200;

    const wrapper = await montar();

    expect(useApi).toHaveBeenCalledWith('/lotes/LOT-20261102-01');
    const texto = wrapper.text();
    expect(texto).toContain('Disco de freno');
    expect(texto).toContain('En proceso');
    expect(texto).toContain('En Fusión desde el');
    expect(texto).toContain('Moldeo');
    expect(texto).toContain('Colada');
    expect(recordarEscaneado).toHaveBeenCalledWith(
      expect.objectContaining({
        codigo: 'LOT-20261102-01',
        producto: 'Disco de freno',
        estado: 'en_proceso',
      }),
    );
  });

  it('no ofrece capturar ni editar nada', async () => {
    respuesta.data = lote;
    respuesta.statusCode = 200;

    const wrapper = await montar();

    expect(wrapper.findAll('button')).toHaveLength(0);
    expect(wrapper.find('form').exists()).toBe(false);
  });

  it('si el lote no existe, ofrece escanear otro', async () => {
    respuesta.error = new Error('Not Found');
    respuesta.statusCode = 404;

    const wrapper = await montar();

    expect(wrapper.text()).toContain('Ese lote no existe');
    const boton = wrapper.findComponent(QBtn);
    expect(boton.text()).toContain('Escanear otro');
    expect(boton.props('to')).toBe('/escanear');
    expect(recordarEscaneado).not.toHaveBeenCalled();
  });
});
