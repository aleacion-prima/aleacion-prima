import { mount } from '@vue/test-utils';
import { Quasar } from 'quasar';
import { describe, expect, it } from 'vitest';
import EssentialLink from './EssentialLink.vue';

describe('EssentialLink', () => {
  it('muestra el título que recibe', () => {
    const wrapper = mount(EssentialLink, {
      props: { label: 'Documentación' },
      global: { plugins: [Quasar] },
    });

    expect(wrapper.text()).toContain('Documentación');
  });
});
