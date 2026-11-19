import { Preferences } from '@capacitor/preferences';
import type { EstadoLote } from '@aleacion-prima/types';

export interface LoteEscaneado {
  codigo: string;
  producto: string;
  estado: EstadoLote;
  consultado_en: string;
}

const CLAVE = 'ultimos-escaneados';
const MAXIMO = 5;

export const leerEscaneados = async (): Promise<LoteEscaneado[]> => {
  const { value } = await Preferences.get({ key: CLAVE });
  return value ? (JSON.parse(value) as LoteEscaneado[]) : [];
};

export const recordarEscaneado = async (lote: LoteEscaneado): Promise<void> => {
  const otros = (await leerEscaneados()).filter((l) => l.codigo !== lote.codigo);
  await Preferences.set({ key: CLAVE, value: JSON.stringify([lote, ...otros].slice(0, MAXIMO)) });
};

export const borrarEscaneados = async (): Promise<void> => {
  await Preferences.remove({ key: CLAVE });
};
