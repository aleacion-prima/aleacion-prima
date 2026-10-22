import { mount } from '@vue/test-utils';
import { Quasar } from 'quasar';
import { QrcodeSvg } from 'qrcode.vue';
import { describe, expect, it } from 'vitest';
import EtiquetaLote from './EtiquetaLote.vue';

describe('EtiquetaLote', () => {
  it('codifica únicamente el código del lote en el QR', () => {
    const wrapper = mount(EtiquetaLote, {
      props: { codigo: 'LOT-20260928-01', aleacion: 'Hierro nodular' },
      global: { plugins: [Quasar] },
    });

    const qr = wrapper.findComponent(QrcodeSvg);
    expect(qr.props('value')).toBe('LOT-20260928-01');
    expect(qr.props('level')).toBe('Q');
    expect(wrapper.find('svg').exists()).toBe(true);
    expect(wrapper.text()).toContain('LOT-20260928-01');
  });
});
