import type { Insumo, ProductoInventario } from '@aleacion-prima/types';

export interface EstiloEstadoInventario {
  etiqueta: string;
  color: string;
}

export const ESTADOS_INSUMO = ['critico', 'por_agotarse', 'optimo'] as const;
export type EstadoInsumo = (typeof ESTADOS_INSUMO)[number];

export const ESTILOS_ESTADO_INSUMO: Record<EstadoInsumo, EstiloEstadoInventario> = {
  critico: { etiqueta: 'Crítico', color: 'negative' },
  por_agotarse: { etiqueta: 'Por agotarse', color: 'warning' },
  optimo: { etiqueta: 'Óptimo', color: 'positive' },
};

// Un insumo "por agotarse" todavía está por encima del stock mínimo, pero sin
// margen: se avisa cuando queda a menos de 20 % de ese mínimo.
const MARGEN_POR_AGOTARSE = 1.2;

export const estadoInsumo = (
  insumo: Pick<Insumo, 'existencia' | 'stock_minimo' | 'bajo_minimo'>,
): EstadoInsumo => {
  if (insumo.bajo_minimo) return 'critico';

  const existencia = Number(insumo.existencia);
  const stockMinimo = Number(insumo.stock_minimo);

  return existencia <= stockMinimo * MARGEN_POR_AGOTARSE ? 'por_agotarse' : 'optimo';
};

export const ESTADOS_PRODUCTO = ['disponible', 'agotado'] as const;
export type EstadoProducto = (typeof ESTADOS_PRODUCTO)[number];

export const ESTILOS_ESTADO_PRODUCTO: Record<EstadoProducto, EstiloEstadoInventario> = {
  disponible: { etiqueta: 'Disponible', color: 'positive' },
  agotado: { etiqueta: 'Agotado', color: 'grey' },
};

export const estadoProducto = (producto: Pick<ProductoInventario, 'existencia'>): EstadoProducto =>
  producto.existencia > 0 ? 'disponible' : 'agotado';

const formatoCantidad = new Intl.NumberFormat('es-MX', { maximumFractionDigits: 3 });
const formatoCosto = new Intl.NumberFormat('es-MX', {
  style: 'currency',
  currency: 'MXN',
  currencyDisplay: 'narrowSymbol',
  minimumFractionDigits: 2,
  maximumFractionDigits: 2,
});

export const formatearCantidad = (cantidad: string | number): string =>
  formatoCantidad.format(Number(cantidad));

export const formatearCosto = (costo: string | number): string =>
  formatoCosto.format(Number(costo));

export const formatearPeso = (peso: string | null): string => {
  if (!peso) return '—';

  const numero = Number(peso);
  return Number.isFinite(numero) ? `${formatoCantidad.format(numero)} kg` : peso;
};
