import { flushPromises, mount } from '@vue/test-utils';
import { Quasar } from 'quasar';
import { describe, expect, it, vi } from 'vitest';
import type { EventoBitacora } from '@aleacion-prima/types';
import BitacoraLote from './BitacoraLote.vue';

const simulado = vi.hoisted(() => ({ respuesta: [] as unknown, falla: false }));

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

const eventos: EventoBitacora[] = [
  {
    id: 'a',
    tipo: 'entrada_area',
    descripcion: 'Entrada a Moldeo: 20 piezas.',
    area: { id: 1, codigo: 'MOL', nombre: 'Moldeo' },
    usuario: 'Ana Pérez',
    datos: { piezas_recibidas: 20 },
    ocurrido_en: '2026-09-28T13:00:00Z',
    registrado_en: '2026-09-29T09:00:00Z',
    captura_tardia: true,
  },
  {
    id: 'b',
    tipo: 'salida_area',
    descripcion: 'Salida de Moldeo: 19 piezas, merma 1.',
    area: { id: 1, codigo: 'MOL', nombre: 'Moldeo' },
    usuario: 'Beto Ruiz',
    datos: {},
    ocurrido_en: '2026-09-28T14:00:00Z',
    registrado_en: '2026-09-28T14:01:00Z',
    captura_tardia: false,
  },
];

const montar = async () => {
  const wrapper = mount(BitacoraLote, {
    props: { codigo: 'LOT-20260928-01', version: 0 },
    global: { plugins: [Quasar] },
  });
  await flushPromises();
  return wrapper;
};

describe('BitacoraLote', () => {
  it('lista los eventos con su etiqueta, descripción, área y quién los registró', async () => {
    simulado.falla = false;
    simulado.respuesta = eventos;

    const texto = (await montar()).text();

    expect(texto).toContain('Entrada a área');
    expect(texto).toContain('Entrada a Moldeo: 20 piezas.');
    expect(texto).toContain('Moldeo · Ana Pérez');
    expect(texto).toContain('Salida de área');
    expect(texto).toContain('Beto Ruiz');
  });

  it('marca solo la captura tardía', async () => {
    simulado.falla = false;
    simulado.respuesta = eventos;

    const wrapper = await montar();

    expect(wrapper.findAll('.q-chip').map((chip) => chip.text())).toEqual(['Captura tardía']);
  });

  it('avisa cuando el lote no tiene eventos', async () => {
    simulado.falla = false;
    simulado.respuesta = [];

    expect((await montar()).text()).toContain('Sin eventos todavía');
  });

  it('avisa cuando no se pudo cargar la bitácora', async () => {
    simulado.falla = true;
    simulado.respuesta = [];

    expect((await montar()).text()).toContain('No se pudo cargar la bitácora');
  });

  it('agrega los eventos nuevos cuando sube la versión, sin volver a abrir la pestaña', async () => {
    simulado.falla = false;
    simulado.respuesta = [eventos[0]];
    const wrapper = await montar();
    expect(wrapper.text()).not.toContain('Salida de Moldeo');

    simulado.respuesta = eventos;
    await wrapper.setProps({ version: 1 });
    await flushPromises();

    expect(wrapper.text()).toContain('Salida de Moldeo: 19 piezas, merma 1.');
  });

  it('si la recarga falla, conserva los eventos que ya mostraba', async () => {
    simulado.falla = false;
    simulado.respuesta = eventos;
    const wrapper = await montar();

    simulado.falla = true;
    await wrapper.setProps({ version: 1 });
    await flushPromises();

    expect(wrapper.text()).toContain('Entrada a Moldeo: 20 piezas.');
    expect(wrapper.text()).not.toContain('No se pudo cargar la bitácora');
  });
});
