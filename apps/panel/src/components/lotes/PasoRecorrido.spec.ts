import { mount } from '@vue/test-utils';
import { Quasar } from 'quasar';
import { describe, expect, it } from 'vitest';
import PasoRecorrido from './PasoRecorrido.vue';

describe('PasoRecorrido', () => {
  it('pinta en verde una parada terminada', () => {
    const wrapper = mount(PasoRecorrido, {
      props: { area: 'MOL', estado: 'terminada' },
      global: { plugins: [Quasar] },
    });

    expect(wrapper.find('.q-avatar').classes()).toContain('bg-positive');
    expect(wrapper.find('.q-icon').text()).toBe('view_module');
  });

  it('usa el icono genérico para un área desconocida', () => {
    const wrapper = mount(PasoRecorrido, {
      props: { area: 'XYZ', estado: 'pendiente' },
      global: { plugins: [Quasar] },
    });

    expect(wrapper.find('.q-icon').text()).toBe('category');
  });
});
