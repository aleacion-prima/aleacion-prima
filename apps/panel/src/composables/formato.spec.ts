import { describe, expect, it } from 'vitest';
import { formatearCantidad, formatearMoneda } from './formato';

describe('formato', () => {
  it('formatea cantidades con separador de miles y sin ceros de más', () => {
    expect(formatearCantidad('25000.000')).toBe('25,000');
    expect(formatearCantidad('182.400')).toBe('182.4');
    expect(formatearCantidad(744.55, 1)).toBe('744.6');
  });

  it('formatea pesos mexicanos', () => {
    expect(formatearMoneda('1276.8000')).toBe('$1,276.80');
  });
});
