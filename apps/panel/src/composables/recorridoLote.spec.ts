import { describe, expect, it } from 'vitest';
import type { Lote, LoteParada } from '@aleacion-prima/types';
import { estadoDeParada, paradasDeLote, resumenRecorrido } from './recorridoLote';

const parada = (
  orden: number,
  codigo: string,
  nombre: string,
  cambios: Partial<LoteParada> = {},
): LoteParada => ({
  id: orden,
  orden,
  area: { id: orden, codigo, nombre },
  piezas_recibidas: null,
  piezas_entregadas: null,
  merma_piezas: null,
  entrada_ocurrido_en: null,
  salida_ocurrido_en: null,
  ...cambios,
});

const lote = (paradas: LoteParada[], estado: Lote['estado'] = 'en_proceso'): Lote => ({
  id: 1,
  codigo: 'LOT-20261102-01',
  estado,
  cantidad_objetivo: 20,
  piezas_terminadas: null,
  producto: { id: 1, codigo: 'DIS-FRE-G', nombre: 'Disco de freno' },
  area_actual: null,
  paradas,
  created_at: '2026-11-02T09:00:00Z',
});

const terminada = {
  entrada_ocurrido_en: '2026-11-02T09:00:00Z',
  salida_ocurrido_en: '2026-11-02T10:00:00Z',
  piezas_entregadas: 20,
};
const enCurso = { entrada_ocurrido_en: '2026-11-02T10:00:00Z', piezas_recibidas: 20 };

describe('recorridoLote', () => {
  it('deduce el estado de cada parada por sus horas', () => {
    expect(estadoDeParada(parada(1, 'MOL', 'Moldeo', terminada))).toBe('terminada');
    expect(estadoDeParada(parada(2, 'FUS', 'Fusión', enCurso))).toBe('en_curso');
    expect(estadoDeParada(parada(3, 'COL', 'Colada'))).toBe('pendiente');
  });

  it('resume dónde va el lote', () => {
    const paradas = [
      parada(1, 'MOL', 'Moldeo', terminada),
      parada(2, 'FUS', 'Fusión', enCurso),
      parada(3, 'COL', 'Colada'),
    ];

    expect(resumenRecorrido(lote(paradas))).toBe('Fusión · 1 de 3');
    expect(resumenRecorrido(lote([parada(1, 'MOL', 'Moldeo')], 'creado'))).toBe(
      'Sin iniciar · 0 de 1',
    );
  });

  it('arma las paradas para el componente del recorrido', () => {
    const resultado = paradasDeLote(
      lote([parada(1, 'MOL', 'Moldeo', terminada), parada(2, 'FUS', 'Fusión', enCurso)]),
    );

    expect(resultado).toEqual([
      { codigo: 'MOL', nombre: 'Moldeo', estado: 'terminada', piezas: '20 pzas entregadas' },
      { codigo: 'FUS', nombre: 'Fusión', estado: 'en_curso', piezas: '20 pzas recibidas' },
    ]);
  });
});
