import { describe, expect, it } from 'vitest';
import {
  estadoInsumo,
  estadoProducto,
  formatearCantidad,
  formatearCosto,
  formatearPeso,
} from './inventario';

describe('estadoInsumo', () => {
  it('marca crítico cuando la existencia está en o bajo el mínimo', () => {
    expect(
      estadoInsumo({ existencia: '900.000', stock_minimo: '1000.000', bajo_minimo: true }),
    ).toBe('critico');
  });

  it('marca por agotarse cuando queda dentro de 20 % del mínimo', () => {
    expect(
      estadoInsumo({ existencia: '1100.000', stock_minimo: '1000.000', bajo_minimo: false }),
    ).toBe('por_agotarse');
  });

  it('marca óptimo cuando hay margen sobre el mínimo', () => {
    expect(
      estadoInsumo({ existencia: '11701.100', stock_minimo: '5000.000', bajo_minimo: false }),
    ).toBe('optimo');
  });
});

describe('estadoProducto', () => {
  it('está disponible mientras haya piezas', () => {
    expect(estadoProducto({ existencia: 3 })).toBe('disponible');
  });

  it('está agotado sin existencia', () => {
    expect(estadoProducto({ existencia: 0 })).toBe('agotado');
  });
});

describe('formato de inventario', () => {
  it('formatea cantidades con separador de miles y hasta tres decimales', () => {
    expect(formatearCantidad('11701.100')).toBe('11,701.1');
    expect(formatearCantidad(5000)).toBe('5,000');
  });

  it('formatea el costo en pesos con dos decimales', () => {
    expect(formatearCosto('7')).toBe('$7.00');
  });

  it('agrega la unidad al peso y deja un guion cuando falta', () => {
    expect(formatearPeso('8.5')).toBe('8.5 kg');
    expect(formatearPeso('hierro gris clase 30')).toBe('hierro gris clase 30');
    expect(formatearPeso(null)).toBe('—');
  });
});
