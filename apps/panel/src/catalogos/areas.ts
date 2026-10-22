export const ICONOS_AREA: Record<string, string> = {
  MOL: 'view_module',
  FUS: 'local_fire_department',
  COL: 'water_drop',
  DES: 'handyman',
  GRA: 'auto_awesome',
  CAL: 'science',
  EMP: 'inventory_2',
};

export const iconoArea = (codigo: string): string => ICONOS_AREA[codigo] ?? 'category';

export const ESTADOS_PARADA = ['terminada', 'en_curso', 'pendiente', 'planeada'] as const;
export type EstadoParada = (typeof ESTADOS_PARADA)[number];

export interface ParadaLote {
  codigo: string;
  nombre: string;
  estado: EstadoParada;
  tiempo?: string;
  piezas?: string;
  aviso?: string;
  obligatoria?: boolean;
}
