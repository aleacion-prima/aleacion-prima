import type { Rol } from '@aleacion-prima/types';

export const ETIQUETAS_ROL: Record<Rol, string> = {
  administrador: 'Administrador',
  ventas: 'Ventas',
  encargado: 'Encargado de área',
  cliente: 'Cliente',
};

export const etiquetaRol = (rol: Rol | null): string => (rol ? ETIQUETAS_ROL[rol] : '—');
