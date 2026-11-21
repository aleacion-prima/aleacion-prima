import { mount } from '@vue/test-utils';
import { Quasar } from 'quasar';
import { describe, expect, it } from 'vitest';
import type { Lote, LoteParada } from '@aleacion-prima/types';
import TarjetaLoteTablero from './TarjetaLoteTablero.vue';

const parada = (orden: number, codigo: string, cambios: Partial<LoteParada> = {}): LoteParada => ({
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
  puede_capturar: true,
  ...cambios,
});

const lote = (cambios: Partial<Lote> = {}): Lote => ({
  id: 1,
  codigo: 'LOT-20260928-01',
  estado: 'en_proceso',
  cantidad_objetivo: 20,
  piezas_terminadas: null,
  producto: { id: 1, codigo: 'CAJ-TRA-N', nombre: 'Caja de transmisión' },
  area_actual: { id: 2, codigo: 'FUS', nombre: 'FUS' },
  costo_total: '8184.6',
  semaforo: 'verde',
  motivos: [],
  paradas: [
    parada(1, 'MOL', {
      entrada_ocurrido_en: '2026-09-28T13:00:00Z',
      salida_ocurrido_en: '2026-09-28T16:00:00Z',
      piezas_entregadas: 20,
    }),
    parada(2, 'FUS', { entrada_ocurrido_en: '2026-09-28T16:00:00Z', piezas_recibidas: 20 }),
    parada(3, 'COL'),
  ],
  confirmado_en: null,
  cancelado_en: null,
  motivo_cancelacion: null,
  admite_devolucion: false,
  created_at: '2026-09-28T12:00:00Z',
  ...cambios,
});

const montar = (props: { lote: Lote }) =>
  mount(TarjetaLoteTablero, {
    props,
    global: {
      plugins: [Quasar],
      stubs: { RouterLink: { props: ['to'], template: '<a :href="to"><slot /></a>' } },
    },
  });

describe('TarjetaLoteTablero', () => {
  it('muestra el lote, su estado, el costo a la fecha y lleva a su detalle', () => {
    const wrapper = montar({ lote: lote() });

    const texto = wrapper.text();
    expect(texto).toContain('LOT-20260928-01');
    expect(texto).toContain('Caja de transmisión');
    expect(texto).toContain('20 pzas');
    expect(texto).toContain('En proceso');
    expect(texto).toMatch(/Costo a la fecha \$8,184\.60/);
    expect(wrapper.find('a').attributes('href')).toBe('/lotes/LOT-20260928-01');
  });

  it('pinta cada parada como terminada, en curso o pendiente', () => {
    const wrapper = montar({ lote: lote() });

    const paradas = wrapper.findAll('.parada');
    expect(paradas).toHaveLength(3);
    expect(paradas[0]?.classes()).toContain('parada--terminada');
    expect(paradas[1]?.classes()).toContain('parada--en_curso');
    expect(paradas[2]?.classes()).toContain('parada--pendiente');
  });

  it('por confirmar muestra el costo final', () => {
    const wrapper = montar({ lote: lote({ estado: 'por_confirmar' }) });

    expect(wrapper.text()).toContain('espera confirmación');
    expect(wrapper.text()).toContain('Costo final');
  });

  it('sin costos no muestra la línea de costo', () => {
    const sinCosto = lote();
    delete sinCosto.costo_total;

    const wrapper = montar({ lote: sinCosto });

    expect(wrapper.text()).not.toContain('Costo');
  });

  it('muestra el semáforo junto al código, aparte del chip de estado', () => {
    const wrapper = montar({
      lote: lote({ semaforo: 'ambar', motivos: ['Merma acumulada de 5 de 100 piezas (5.0 %).'] }),
    });

    const punto = wrapper.find('[data-semaforo="ambar"]');
    expect(punto.exists()).toBe(true);
    expect(punto.attributes('aria-label')).toContain('Merma acumulada');
    expect(punto.classes()).not.toContain('q-chip');
    expect(wrapper.find('.q-chip').text()).toContain('En proceso');
  });

  it('marca el punto cuando el semáforo viene incompleto', () => {
    const wrapper = montar({
      lote: lote({ semaforo: 'verde', motivos: [], semaforo_incompleto: true }),
    });

    expect(wrapper.find('[data-semaforo="verde"]').classes()).toContain(
      'punto-semaforo--incompleto',
    );
  });

  it('sin semáforo no pinta el punto', () => {
    const wrapper = montar({ lote: lote({ semaforo: null, motivos: [] }) });

    expect(wrapper.find('.punto-semaforo').exists()).toBe(false);
  });
});
