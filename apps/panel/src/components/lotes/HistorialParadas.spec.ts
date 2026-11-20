import { flushPromises, mount } from '@vue/test-utils';
import { Quasar } from 'quasar';
import { describe, expect, it, vi } from 'vitest';
import type { HistorialLote, HistorialParada } from '@aleacion-prima/types';
import HistorialParadas from './HistorialParadas.vue';

const simulado = vi.hoisted((): { respuesta: unknown; falla: boolean } => ({
  respuesta: null,
  falla: false,
}));

vi.mock('@/services/api', async () => {
  const { ref } = await import('vue');
  return {
    useApi: () => ({
      json: () =>
        Promise.resolve({
          data: ref(simulado.respuesta),
          error: ref(simulado.falla ? new Error('falla') : null),
        }),
    }),
  };
});

const parada = (
  orden: number,
  codigo: string,
  cambios: Partial<HistorialParada> = {},
): HistorialParada => ({
  id: orden,
  orden,
  area: { id: orden, codigo, nombre: codigo },
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
  puede_capturar: false,
  entrada_por: null,
  salida_por: null,
  ...cambios,
});

const historial: HistorialLote = {
  codigo: 'LOT-20260928-01',
  estado: 'en_proceso',
  paradas: [
    parada(1, 'MOL', {
      piezas_recibidas: 20,
      piezas_entregadas: 19,
      merma_piezas: 1,
      entrada_ocurrido_en: '2026-09-28T13:00:00Z',
      entrada_tardia: true,
      entrada_por: 'Ana Pérez',
      salida_ocurrido_en: '2026-09-28T14:00:00Z',
      salida_por: 'Beto Ruiz',
    }),
    parada(2, 'FUS', {
      piezas_recibidas: 17,
      discrepancia: -2,
      entrada_ocurrido_en: '2026-09-28T14:05:00Z',
      entrada_por: 'Beto Ruiz',
    }),
    parada(3, 'COL'),
  ],
};

const montar = async () => {
  const wrapper = mount(HistorialParadas, {
    props: { codigo: 'LOT-20260928-01', version: 0 },
    global: { plugins: [Quasar] },
  });
  await flushPromises();
  return wrapper;
};

describe('HistorialParadas', () => {
  it('muestra las paradas en orden con quién capturó, la merma y las marcas', async () => {
    simulado.falla = false;
    simulado.respuesta = historial;

    const wrapper = await montar();
    const filas = wrapper.findAll('tbody tr');
    const texto = wrapper.text();

    expect(filas).toHaveLength(3);
    expect(filas[0]?.text()).toContain('1 · MOL');
    expect(filas[1]?.text()).toContain('2 · FUS');
    expect(texto).toContain('Ana Pérez');
    expect(texto).toContain('Beto Ruiz');
    expect(filas[0]?.text()).toContain('1 pza');
    expect(filas[0]?.text()).toContain('Captura tardía');
    expect(filas[1]?.text()).toContain('Diferencia -2 pzas');
  });

  it('marca como pendiente lo que todavía no se captura', async () => {
    simulado.falla = false;
    simulado.respuesta = historial;

    const filas = (await montar()).findAll('tbody tr');

    expect(filas[2]?.text()).toContain('Pendiente');
  });

  it('avisa cuando no se pudo cargar el historial', async () => {
    simulado.falla = true;
    simulado.respuesta = null;

    expect((await montar()).text()).toContain('No se pudo cargar el historial');
  });
});
