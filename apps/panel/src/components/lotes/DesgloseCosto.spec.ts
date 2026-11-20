import { flushPromises, mount } from '@vue/test-utils';
import { Quasar } from 'quasar';
import { describe, expect, it, vi } from 'vitest';
import type { CostoLote } from '@aleacion-prima/types';
import DesgloseCosto from './DesgloseCosto.vue';

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

const referencia: CostoLote = {
  codigo: 'LOT-20260924-01',
  estado: 'terminado',
  congelado: true,
  piezas_terminadas: 46,
  material: '4956.2500',
  mano_obra: '2295.0000',
  maquina: '3150.0000',
  total: '10401.2500',
  por_pieza: '226.1141',
};

const montar = async () => {
  const wrapper = mount(DesgloseCosto, {
    props: { codigo: 'LOT-20260924-01', version: 0 },
    global: { plugins: [Quasar] },
  });
  await flushPromises();
  return wrapper;
};

describe('DesgloseCosto', () => {
  it('muestra $10,401.25 total y $226.11 por pieza en el lote de referencia', async () => {
    simulado.falla = false;
    simulado.respuesta = referencia;

    const texto = (await montar()).text();

    expect(texto).toContain('Costo final');
    expect(texto).toContain('Congelado');
    expect(texto).toContain('$4,956.25');
    expect(texto).toContain('$2,295.00');
    expect(texto).toContain('$3,150.00');
    expect(texto).toContain('$10,401.25');
    expect(texto).toContain('$226.11');
  });

  it('con el lote abierto es el costo a la fecha y el costo por pieza queda pendiente', async () => {
    simulado.falla = false;
    simulado.respuesta = {
      ...referencia,
      estado: 'en_proceso',
      congelado: false,
      piezas_terminadas: null,
      total: '865.0000',
      por_pieza: null,
    };

    const texto = (await montar()).text();

    expect(texto).toContain('Costo a la fecha');
    expect(texto).toContain('En curso');
    expect(texto).toContain('Se calcula al confirmar el cierre del lote.');
    expect(texto).not.toContain('$226.11');
  });

  it('avisa cuando no se pudo cargar el costo', async () => {
    simulado.falla = true;
    simulado.respuesta = null;

    expect((await montar()).text()).toContain('No se pudo cargar el costo');
  });
});
