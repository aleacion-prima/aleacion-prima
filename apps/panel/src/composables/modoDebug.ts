import type { Lote, Usuario } from '@aleacion-prima/types';
import { useApi } from '@/services/api';

export interface Avance {
  hasta?: 'siguiente' | 'final';
  merma?: number;
  faltante?: number;
}

const pedir = async <T>(ruta: string, cuerpo?: object): Promise<T> => {
  const peticion = useApi(`/debug${ruta}`);
  const { data, error } = await (cuerpo ? peticion.post(cuerpo) : peticion.get()).json<
    T & { message?: string }
  >();
  if (error.value) throw new Error(data.value?.message ?? 'El truco falló');
  return data.value as T;
};

export const listarUsuarios = (): Promise<Usuario[]> => pedir<Usuario[]>('/usuarios');

export const entrarComo = (id: number): Promise<Usuario> =>
  pedir<Usuario>(`/entrar-como/${id}`, {});

export const crearLote = (): Promise<Lote> => pedir<Lote>('/lotes', {});

export const avanzarLote = (codigo: string, avance: Avance): Promise<Lote> =>
  pedir<Lote>(`/lotes/${codigo}/avanzar`, avance);

export const crearVariosLotes = (lotes: number): Promise<Lote[]> =>
  pedir<Lote[]>('/lotes/varios', { lotes });

export const alternarAreas = (): Promise<Usuario> => pedir<Usuario>('/areas/alternar', {});

export const rellenarInventario = async (): Promise<void> => {
  const { error } = await useApi('/debug/inventario/rellenar').post();
  if (error.value) throw new Error('No se pudo rellenar el inventario');
};

export const reiniciarBd = async (): Promise<void> => {
  const { error } = await useApi('/debug/reiniciar').post();
  if (error.value) throw new Error('No se pudo reiniciar la BD');
};
