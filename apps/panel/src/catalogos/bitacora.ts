import type { TipoEventoBitacora } from '@aleacion-prima/types';

export const ETIQUETAS_EVENTO_BITACORA: Record<TipoEventoBitacora, string> = {
  creacion: 'Creación',
  asignacion_insumo: 'Asignación de insumo',
  entrada_area: 'Entrada a área',
  salida_area: 'Salida de área',
  imprevisto: 'Imprevisto',
  retorno: 'Retorno de metal',
  devolucion_insumo: 'Devolución de insumo',
  cancelacion: 'Cancelación',
  confirmacion: 'Confirmación',
  ajuste: 'Ajuste',
  stock_minimo: 'Stock mínimo',
};

export const ICONOS_EVENTO_BITACORA: Record<TipoEventoBitacora, string> = {
  creacion: 'add_circle',
  asignacion_insumo: 'inventory_2',
  entrada_area: 'login',
  salida_area: 'logout',
  imprevisto: 'warning',
  retorno: 'replay',
  devolucion_insumo: 'undo',
  cancelacion: 'block',
  confirmacion: 'task_alt',
  ajuste: 'tune',
  stock_minimo: 'notification_important',
};
