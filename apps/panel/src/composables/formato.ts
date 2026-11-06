const numero = (decimales: number): Intl.NumberFormat =>
  new Intl.NumberFormat('es-MX', { minimumFractionDigits: 0, maximumFractionDigits: decimales });

const moneda = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' });

export const formatearCantidad = (valor: string | number, decimales = 3): string =>
  numero(decimales).format(Number(valor));

export const formatearMoneda = (valor: string | number): string => moneda.format(Number(valor));

export const piezas = (cantidad: number): string =>
  `${cantidad} ${Math.abs(cantidad) === 1 ? 'pieza' : 'piezas'}`;

export const pzas = (cantidad: number): string =>
  `${cantidad} ${Math.abs(cantidad) === 1 ? 'pza' : 'pzas'}`;

export const conPlural = (cantidad: number, singular: string, plural: string): string =>
  Math.abs(cantidad) === 1 ? singular : plural;
