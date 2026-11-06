import { describe, expect, it } from 'vitest';
import { conPlural, formatearCantidad, formatearMoneda, piezas, pzas } from './formato';

describe('formato', () => {
  it('formatea cantidades con separador de miles y sin ceros de más', () => {
    expect(formatearCantidad('25000.000')).toBe('25,000');
    expect(formatearCantidad('182.400')).toBe('182.4');
    expect(formatearCantidad(744.55, 1)).toBe('744.6');
  });

  it('formatea pesos mexicanos', () => {
    expect(formatearMoneda('1276.8000')).toBe('$1,276.80');
  });

  it('usa singular solo con una pieza', () => {
    expect(piezas(1)).toBe('1 pieza');
    expect(piezas(0)).toBe('0 piezas');
    expect(pzas(-1)).toBe('-1 pza');
    expect(pzas(20)).toBe('20 pzas');
    expect(conPlural(1, 'entra', 'entran')).toBe('entra');
    expect(conPlural(4, 'entra', 'entran')).toBe('entran');
  });
});
