import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import NumeroAnimado from './NumeroAnimado.vue';

describe('NumeroAnimado', () => {
  it('llega al valor final con su formato', async () => {
    const wrapper = mount(NumeroAnimado, { props: { valor: '1276.8', formato: 'moneda' } });

    await vi.waitFor(() => expect(wrapper.text()).toBe('$1,276.80'));
  });

  it('se mueve al nuevo valor cuando cambia', async () => {
    const wrapper = mount(NumeroAnimado, { props: { valor: 10, formato: 'entero' } });
    await vi.waitFor(() => expect(wrapper.text()).toBe('10'));

    await wrapper.setProps({ valor: 46 });

    await vi.waitFor(() => expect(wrapper.text()).toBe('46'));
  });
});
