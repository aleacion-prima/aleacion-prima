import { flushPromises, mount } from '@vue/test-utils';
import { QBtn, Quasar } from 'quasar';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import type { Imprevisto, Lote, LoteParada } from '@aleacion-prima/types';
import LoteMovilPage from './LoteMovilPage.vue';

const respuesta = vi.hoisted(
  (): { data: Lote | null; error: Error | null; statusCode: number | null } => ({
    data: null,
    error: null,
    statusCode: null,
  }),
);
const imprevistosRespuesta = vi.hoisted(() => ({ data: [] as Imprevisto[] }));
const useApi = vi.fn((url: string) => ({
  json: () =>
    Promise.resolve({
      url,
      data: { value: url.endsWith('/imprevistos') ? imprevistosRespuesta.data : respuesta.data },
      error: { value: respuesta.error },
      statusCode: { value: respuesta.statusCode },
    }),
}));

vi.mock('@/services/api', () => ({ useApi: (url: string) => useApi(url) }));
const recordarEscaneado = vi.fn();
vi.mock('@/services/escaneados', () => ({
  recordarEscaneado: (lote: unknown) => recordarEscaneado(lote),
}));
const enVivo = vi.hoisted(() => ({
  alCambiar: undefined as ((evento: { codigo: string }) => void) | undefined,
}));
vi.mock('@/composables/lotesEnVivo', () => ({
  useCanalesDeLotes: () => ['tablero'],
  useLotesEnVivo: (_canales: unknown, alCambiar: (evento: { codigo: string }) => void) => {
    enVivo.alCambiar = alCambiar;
  },
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
  imprevistosRespuesta.data = [];
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

  it('vuelve a pedir el lote cuando cambia en vivo, sin mostrar el spinner', async () => {
    respuesta.data = lote;
    respuesta.statusCode = 200;
    const wrapper = await montar();
    useApi.mockClear();

    enVivo.alCambiar?.({ codigo: 'LOT-20261102-99' });
    await flushPromises();
    expect(useApi).not.toHaveBeenCalled();

    enVivo.alCambiar?.({ codigo: 'LOT-20261102-01' });
    expect(wrapper.find('.q-spinner').exists()).toBe(false);
    await flushPromises();
    expect(useApi).toHaveBeenCalledWith('/lotes/LOT-20261102-01');
  });

  it('muestra los imprevistos del lote sin ofrecer resolverlos', async () => {
    respuesta.data = lote;
    respuesta.statusCode = 200;
    imprevistosRespuesta.data = [
      {
        id: 'abc',
        lote_id: 1,
        tipo: 'falla_maquina',
        descripcion: 'Se trabó el horno',
        maquina: null,
        piezas_afectadas: null,
        duracion_minutos: null,
        resuelto: false,
        registrado_por: null,
        ocurrido_en: '2026-11-20T16:50:00Z',
        created_at: '2026-11-20T16:50:00Z',
        area: { id: 2, codigo: 'FUS', nombre: 'Fusión' },
      },
    ];

    const wrapper = await montar();

    expect(useApi).toHaveBeenCalledWith('/lotes/LOT-20261102-01/imprevistos');
    expect(wrapper.text()).toContain('Falla de máquina');
    expect(wrapper.text()).toContain('Se trabó el horno');
    expect(wrapper.text()).toContain('Abierto');
    expect(wrapper.findAll('button')).toHaveLength(0);
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
