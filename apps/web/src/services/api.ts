import type { ProductoPublico } from '@aleacion-prima/types';
import { PUBLIC_API_URL } from 'astro:env/client';

const pedir = async <T>(ruta: string): Promise<T | null> => {
  try {
    const respuesta = await fetch(`${PUBLIC_API_URL}/api/publico${ruta}`, {
      headers: { Accept: 'application/json' },
    });
    return respuesta.ok ? ((await respuesta.json()) as T) : null;
  } catch {
    return null;
  }
};

export const listarProductos = (): Promise<ProductoPublico[] | null> =>
  pedir<ProductoPublico[]>('/productos');

export const obtenerProducto = (id: number): Promise<ProductoPublico | null> =>
  pedir<ProductoPublico>(`/productos/${id}`);
