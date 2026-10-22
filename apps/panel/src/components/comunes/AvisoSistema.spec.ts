import { mount } from '@vue/test-utils';
import { Quasar } from 'quasar';
import { describe, expect, it } from 'vitest';
import AvisoSistema from './AvisoSistema.vue';

describe('AvisoSistema', () => {
  it('muestra el mensaje con el icono del tipo', () => {
    const wrapper = mount(AvisoSistema, {
      props: { tipo: 'advertencia', mensaje: 'Captura tardía' },
      global: { plugins: [Quasar] },
    });

    expect(wrapper.text()).toContain('Captura tardía');
    expect(wrapper.find('.q-icon').text()).toBe('warning');
    expect(wrapper.find('.q-icon').classes()).toContain('text-warning');
  });

  it('emite cerrar al pulsar la equis y se puede ocultar', async () => {
    const wrapper = mount(AvisoSistema, {
      props: { mensaje: 'Lote creado' },
      global: { plugins: [Quasar] },
    });

    await wrapper.find('button').trigger('click');
    expect(wrapper.emitted('cerrar')).toHaveLength(1);

    const fijo = mount(AvisoSistema, {
      props: { mensaje: 'Lote creado', cerrable: false },
      global: { plugins: [Quasar] },
    });
    expect(fijo.find('button').exists()).toBe(false);
  });
});
