import { mount } from '@vue/test-utils';
import { Quasar } from 'quasar';
import { describe, expect, it } from 'vitest';
import EstadoVacio from './EstadoVacio.vue';

describe('EstadoVacio', () => {
  it('muestra el título y la descripción', () => {
    const wrapper = mount(EstadoVacio, {
      props: { titulo: 'Sin lotes', descripcion: 'Crea el primero' },
      global: { plugins: [Quasar] },
    });

    expect(wrapper.text()).toContain('Sin lotes');
    expect(wrapper.text()).toContain('Crea el primero');
  });

  it('acepta una acción en el slot', () => {
    const wrapper = mount(EstadoVacio, {
      props: { titulo: 'Sin lotes' },
      slots: { default: '<button>Nuevo lote</button>' },
      global: { plugins: [Quasar] },
    });

    expect(wrapper.find('button').text()).toBe('Nuevo lote');
  });
});
