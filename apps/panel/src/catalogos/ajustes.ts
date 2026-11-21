import type { CampoAjusteLote, LoteInsumoAsignado, LoteParada } from '@aleacion-prima/types';

export type TipoValorAjuste = 'entero' | 'decimal' | 'fecha';

export type ObjetivoAjuste =
  { tipo: 'parada'; parada: LoteParada } | { tipo: 'insumo'; insumo: LoteInsumoAsignado };

export interface CampoAjustable {
  campo: CampoAjusteLote;
  etiqueta: string;
  tipo: TipoValorAjuste;
}

export const CAMPOS_PARADA: CampoAjustable[] = [
  { campo: 'piezas_recibidas', etiqueta: 'Piezas recibidas', tipo: 'entero' },
  { campo: 'piezas_entregadas', etiqueta: 'Piezas entregadas', tipo: 'entero' },
  { campo: 'merma_material', etiqueta: 'Merma de material (kg)', tipo: 'decimal' },
  { campo: 'entrada_ocurrido_en', etiqueta: 'Hora de entrada', tipo: 'fecha' },
  { campo: 'salida_ocurrido_en', etiqueta: 'Hora de salida', tipo: 'fecha' },
];

export const CAMPOS_INSUMO: CampoAjustable[] = [
  { campo: 'cantidad', etiqueta: 'Cantidad asignada', tipo: 'decimal' },
  { campo: 'cantidad_devuelta', etiqueta: 'Cantidad devuelta', tipo: 'decimal' },
];

export const ETIQUETAS_CAMPO: Record<CampoAjusteLote, string> = {
  piezas_recibidas: 'Piezas recibidas',
  piezas_entregadas: 'Piezas entregadas',
  merma_material: 'Merma de material (kg)',
  entrada_ocurrido_en: 'Hora de entrada',
  salida_ocurrido_en: 'Hora de salida',
  cantidad: 'Cantidad asignada',
  cantidad_devuelta: 'Cantidad devuelta',
};

export const etiquetaCampo = (campo: CampoAjusteLote): string => ETIQUETAS_CAMPO[campo];
