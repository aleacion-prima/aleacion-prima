import type { EstadoLote } from '@aleacion-prima/types';

export interface EstiloEstadoLote {
  etiqueta: string;
  color: string;
  colorTexto: string;
}

export const ESTILOS_ESTADO_LOTE: Record<EstadoLote, EstiloEstadoLote> = {
  creado: { etiqueta: 'Creado', color: 'info', colorTexto: 'white' },
  en_proceso: { etiqueta: 'En proceso', color: 'accent', colorTexto: 'dark' },
  por_confirmar: { etiqueta: 'Por confirmar', color: 'warning', colorTexto: 'white' },
  terminado: { etiqueta: 'Terminado', color: 'positive', colorTexto: 'white' },
  cancelado: { etiqueta: 'Cancelado', color: 'negative', colorTexto: 'white' },
};
