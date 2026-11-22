import type { Notificacion } from '@aleacion-prima/types';

export interface EstiloNotificacion {
  etiqueta: string;
  color: string;
}

export const ESTILOS_NOTIFICACION: Record<Notificacion['tipo'], EstiloNotificacion> = {
  imprevisto: { etiqueta: 'Imprevisto', color: 'negative' },
  stock_minimo: { etiqueta: 'Stock mínimo', color: 'info' },
  lote_por_confirmar: { etiqueta: 'Por confirmar', color: 'warning' },
};

const formatoDia = new Intl.DateTimeFormat('es-MX', { day: 'numeric', month: 'short' });
const formatoHora = new Intl.DateTimeFormat('es-MX', {
  hour: '2-digit',
  minute: '2-digit',
  hour12: false,
});

export const fechaDeNotificacion = (valor: string): string => {
  const fecha = new Date(valor);
  if (Number.isNaN(fecha.getTime())) return valor;

  return `${formatoDia.format(fecha).replace('.', '')}, ${formatoHora.format(fecha)}`;
};

export const tituloDeNotificacion = (notificacion: Notificacion): string =>
  notificacion.mensaje.split('\n')[0] ?? notificacion.mensaje;

export const detalleDeNotificacion = (notificacion: Notificacion): string =>
  notificacion.mensaje.split('\n').slice(1).join(' ').trim();

export const accionDeNotificacion = (notificacion: Notificacion): string =>
  notificacion.tipo === 'stock_minimo' ? 'Ver insumo' : 'Ver lote';

export const rutaDeNotificacion = (notificacion: Notificacion): string | null => {
  if (!notificacion.codigo) return null;

  return notificacion.tipo === 'stock_minimo'
    ? `/inventario/insumos?buscar=${encodeURIComponent(notificacion.codigo)}`
    : `/lotes/${encodeURIComponent(notificacion.codigo)}`;
};
