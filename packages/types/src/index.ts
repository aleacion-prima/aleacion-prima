export interface ParadaRecorrido {
  area_id: number;
  orden: number;
}

// Inventario
export const TIPOS_MOVIMIENTO_INSUMO = [
  'entrada',
  'asignacion',
  'devolucion',
  'retorno',
  'ajuste',
] as const;
export type TipoMovimientoInsumo = (typeof TIPOS_MOVIMIENTO_INSUMO)[number];

export const TIPOS_MOVIMIENTO_PRODUCTO = ['produccion', 'venta', 'ajuste'] as const;
export type TipoMovimientoProducto = (typeof TIPOS_MOVIMIENTO_PRODUCTO)[number];

export const CATEGORIAS_INSUMO = ['metal', 'moldeo', 'empaque'] as const;
export type CategoriaInsumo = (typeof CATEGORIAS_INSUMO)[number];

// Ventas (recortable)
export const ESTADOS_PEDIDO = ['borrador', 'confirmado', 'entregado', 'cancelado'] as const;
export type EstadoPedido = (typeof ESTADOS_PEDIDO)[number];

export const ESTADOS_PAGO = ['pendiente', 'parcial', 'pagado'] as const;
export type EstadoPago = (typeof ESTADOS_PAGO)[number];

// ---- MongoDB ----
// imprevistos (tipo)
export const TIPOS_IMPREVISTO = [
  'falla_maquina',
  'retraso_material',
  'pieza_defectuosa',
  'otro',
] as const;
export type TipoImprevisto = (typeof TIPOS_IMPREVISTO)[number];

// bitacora (tipo)
export const TIPOS_EVENTO_BITACORA = [
  'creacion',
  'asignacion_insumo',
  'entrada_area',
  'salida_area',
  'imprevisto',
  'retorno',
  'devolucion_insumo',
  'cancelacion',
  'confirmacion',
  'ajuste',
  'stock_minimo',
] as const;
export type TipoEventoBitacora = (typeof TIPOS_EVENTO_BITACORA)[number];

// Tiempo real (web publica)
export interface DisponibilidadActualizada {
  id: number;
  disponible: number;
}

// Lotes y roles
export const ESTADOS_LOTE = [
  'creado',
  'en_proceso',
  'por_confirmar',
  'terminado',
  'cancelado',
] as const;
export type EstadoLote = (typeof ESTADOS_LOTE)[number];

export const ROLES = ['administrador', 'ventas', 'encargado', 'cliente'] as const;
export type Rol = (typeof ROLES)[number];

export interface Lote {
  id: number;
  codigo: string;
  producto_id: number;
  pedido_id: number | null;
  estado: EstadoLote;
  area_actual_id: number | null;
  cantidad_objetivo: number;
  piezas_terminadas: number | null;
  costo_total: string;
  created_at: string;
}

export interface Usuario {
  id: number;
  name: string;
  email: string;
  rol: Rol | null;
}

export interface Area {
  id: number;
  codigo: string;
  nombre: string;
  descripcion: string | null;
  tarifa_mano_obra_hora: string;
  costo_maquina_hora: string;
  obligatoria: boolean;
  orden_catalogo: number;
  activo: boolean;
}
