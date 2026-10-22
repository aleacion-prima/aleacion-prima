import { mount } from '@vue/test-utils';
import { Quasar } from 'quasar';
import { describe, expect, it } from 'vitest';
import { ESTADOS_LOTE } from '@aleacion-prima/types';
import ChipEstado from './ChipEstado.vue';

describe('ChipEstado', () => {
  it.each(ESTADOS_LOTE)('muestra la etiqueta del estado %s', (estado) => {
    const wrapper = mount(ChipEstado, { props: { estado }, global: { plugins: [Quasar] } });

    expect(wrapper.text()).not.toBe('');
    expect(wrapper.text()).not.toContain('_');
  });

  it('usa texto oscuro sobre el naranja de en proceso', () => {
    const wrapper = mount(ChipEstado, {
      props: { estado: 'en_proceso' },
      global: { plugins: [Quasar] },
    });

    expect(wrapper.find('.q-chip').classes()).toContain('bg-accent');
    expect(wrapper.find('.q-chip').classes()).toContain('text-dark');
  });
});
