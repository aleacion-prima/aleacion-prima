import { describe, expect, it } from 'vitest';
import { esCodigoLote, normalizarCodigo } from './codigoLote';

describe('codigoLote', () => {
  it.each(['LOT-20260928-01', 'LOT-20261113-100', ' lot-20260928-01 '])('acepta %s', (texto) => {
    expect(esCodigoLote(texto)).toBe(true);
  });

  it.each([
    '',
    'https://aleacionprima.mx/lotes/LOT-20260928-01',
    'LOT-2026-0042',
    'LOT-20260928-1',
    'LOT-20260928-01 extra',
    'CAJ-TRA-N',
  ])('rechaza %s', (texto) => {
    expect(esCodigoLote(texto)).toBe(false);
  });

  it('normaliza espacios y minúsculas', () => {
    expect(normalizarCodigo('  lot-20260928-01 ')).toBe('LOT-20260928-01');
  });
});
