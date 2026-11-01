import { describe, expect, it } from 'vitest';
import type { Lote, LoteParada } from '@aleacion-prima/types';
import { lotesEnArea, lotesPorLlegar, movimientosDeHoy } from './miArea';

const parada = (orden: number, codigo: string, cambios: Partial<LoteParada> = {}): LoteParada => ({
  id: orden * 10,
  orden,
  area: { id: orden, codigo, nombre: codigo },
  piezas_recibidas: null,
  piezas_entregadas: null,
  merma_piezas: null,
  merma_material: null,
  retorno_kg: null,
  discrepancia: null,
  entrada_ocurrido_en: null,
  entrada_registrado_en: null,
  entrada_tardia: false,
  salida_ocurrido_en: null,
  salida_registrado_en: null,
  salida_tardia: false,
  admite_retorno: false,
  puede_capturar: true,
  ...cambios,
});

const lote = (
  codigo: string,
  paradas: LoteParada[],
  estado: Lote['estado'] = 'en_proceso',
): Lote => ({
  id: Number(codigo.slice(-2)),
  codigo,
  estado,
  cantidad_objetivo: 20,
  piezas_terminadas: null,
  producto: { id: 1, codigo: 'DIS-FRE-G', nombre: 'Disco de freno' },
  area_actual: null,
  paradas,
  created_at: '2026-11-02T06:00:00Z',
});

const terminada = (hora: string) => ({
  entrada_ocurrido_en: `2026-11-02T${hora}:00Z`,
  salida_ocurrido_en: `2026-11-02T${hora}:30Z`,
  piezas_recibidas: 20,
  piezas_entregadas: 20,
});
const enCurso = (hora: string) => ({
  entrada_ocurrido_en: `2026-11-02T${hora}:00Z`,
  piezas_recibidas: 20,
});

const enMoldeo = lote('LOT-20261102-01', [parada(1, 'MOL', enCurso('17')), parada(2, 'FUS')]);
const listoParaFusion = lote('LOT-20261102-02', [
  parada(1, 'MOL', terminada('16')),
  parada(2, 'FUS'),
]);
const nuevo = lote('LOT-20261102-03', [parada(1, 'MOL'), parada(2, 'FUS')], 'creado');
const terminado = lote('LOT-20261102-04', [parada(1, 'MOL', terminada('15'))], 'por_confirmar');

describe('miArea', () => {
  it('lista los lotes que están ahora en el área', () => {
    expect(lotesEnArea([enMoldeo, listoParaFusion], 'MOL').map((item) => item.lote.codigo)).toEqual(
      ['LOT-20261102-01'],
    );
  });

  it('separa los que ya pueden entrar de los que siguen en el área anterior', () => {
    const fusion = lotesPorLlegar([enMoldeo, listoParaFusion, nuevo, terminado], 'FUS');

    expect(fusion.map((item) => [item.lote.codigo, item.disponible])).toEqual([
      ['LOT-20261102-01', false],
      ['LOT-20261102-02', true],
    ]);
    expect(lotesPorLlegar([nuevo], 'MOL').map((item) => item.disponible)).toEqual([true]);
  });

  it('respeta si el usuario puede capturar en esa parada', () => {
    const ajeno = lote('LOT-20261102-05', [parada(1, 'MOL', { puede_capturar: false })], 'creado');

    expect(lotesPorLlegar([ajeno], 'MOL')[0]?.disponible).toBe(false);
  });

  it('arma los movimientos del día, del más reciente al más viejo', () => {
    const movimientos = movimientosDeHoy(
      [enMoldeo, terminado],
      'MOL',
      new Date('2026-11-02T20:00:00Z'),
    );

    expect(movimientos.map((m) => `${m.lote.codigo} ${m.evento}`)).toEqual([
      'LOT-20261102-01 Entrada',
      'LOT-20261102-04 Salida',
      'LOT-20261102-04 Entrada',
    ]);
  });
});
