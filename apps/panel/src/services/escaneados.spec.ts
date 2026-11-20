import { beforeEach, describe, expect, it, vi } from 'vitest';
import {
  borrarEscaneados,
  leerEscaneados,
  recordarEscaneado,
  type LoteEscaneado,
} from './escaneados';

const preferencias = vi.hoisted(() => new Map<string, string>());

vi.mock('@capacitor/preferences', () => ({
  Preferences: {
    get: ({ key }: { key: string }) => Promise.resolve({ value: preferencias.get(key) ?? null }),
    set: ({ key, value }: { key: string; value: string }) => {
      preferencias.set(key, value);
      return Promise.resolve();
    },
    remove: ({ key }: { key: string }) => {
      preferencias.delete(key);
      return Promise.resolve();
    },
  },
}));

const escaneado = (n: number): LoteEscaneado => ({
  codigo: `LOT-20261102-0${n}`,
  producto: 'Disco de freno',
  estado: 'en_proceso',
  consultado_en: '2026-11-02T10:00:00Z',
});

beforeEach(() => {
  preferencias.clear();
});

describe('escaneados', () => {
  it('sin nada guardado devuelve una lista vacía', async () => {
    expect(await leerEscaneados()).toEqual([]);
  });

  it('pone el último consultado primero y no repite códigos', async () => {
    await recordarEscaneado(escaneado(1));
    await recordarEscaneado(escaneado(2));
    await recordarEscaneado({ ...escaneado(1), estado: 'terminado' });

    const lista = await leerEscaneados();
    expect(lista.map((l) => l.codigo)).toEqual(['LOT-20261102-01', 'LOT-20261102-02']);
    expect(lista[0]?.estado).toBe('terminado');
  });

  it('guarda solo los últimos 5', async () => {
    for (const n of [1, 2, 3, 4, 5, 6]) await recordarEscaneado(escaneado(n));

    const lista = await leerEscaneados();
    expect(lista).toHaveLength(5);
    expect(lista[0]?.codigo).toBe('LOT-20261102-06');
    expect(lista.some((l) => l.codigo === 'LOT-20261102-01')).toBe(false);
  });

  it('se borran al cerrar sesión', async () => {
    await recordarEscaneado(escaneado(1));

    await borrarEscaneados();

    expect(await leerEscaneados()).toEqual([]);
  });
});
