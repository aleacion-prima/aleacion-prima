import { TIPOS_IMPREVISTO, type TipoImprevisto } from '@aleacion-prima/types';

export const ETIQUETAS_TIPO_IMPREVISTO: Record<TipoImprevisto, string> = {
  falla_maquina: 'Falla de máquina',
  retraso_material: 'Retraso de material',
  pieza_defectuosa: 'Pieza defectuosa',
  otro: 'Otro',
};

export const opcionesTipoImprevisto = TIPOS_IMPREVISTO.map((valor) => ({
  label: ETIQUETAS_TIPO_IMPREVISTO[valor],
  value: valor,
}));
