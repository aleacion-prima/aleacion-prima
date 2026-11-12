import { mount } from '@vue/test-utils';
import { Quasar } from 'quasar';
import { QrcodeSvg } from 'qrcode.vue';
import { describe, expect, it } from 'vitest';
import EtiquetaLote from './EtiquetaLote.vue';

const lote = {
  codigo: 'LOT-20260928-01',
  producto: { id: 1, codigo: 'CAJ-TRA-N', nombre: 'Caja de transmisión' },
  cantidad_objetivo: 20,
  created_at: '2026-09-28T15:00:00Z',
};

const montar = (props = { lote }) => mount(EtiquetaLote, { props, global: { plugins: [Quasar] } });

describe('EtiquetaLote', () => {
  it('codifica únicamente el código del lote en el QR', () => {
    const qr = montar().findComponent(QrcodeSvg);

    expect(qr.props('value')).toBe('LOT-20260928-01');
    expect(qr.props('level')).toBe('Q');
    expect(qr.props('margin')).toBe(4);
  });

  it('muestra el código, el producto, las piezas y la fecha de creación', () => {
    const texto = montar().text();

    expect(texto).toContain('LOT-20260928-01');
    expect(texto).toContain('Caja de transmisión');
    expect(texto).toContain('CAJ-TRA-N · 20 piezas');
    expect(texto).toMatch(/Creado 28 \w+\.? 2026/);
  });

  it('usa singular con una sola pieza', () => {
    const wrapper = montar({ lote: { ...lote, cantidad_objetivo: 1 } });

    expect(wrapper.text()).toContain('CAJ-TRA-N · 1 pieza');
  });
});
