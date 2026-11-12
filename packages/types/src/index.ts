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

export interface Insumo {
  id: number;
  codigo: string;
  nombre: string;
  categoria: CategoriaInsumo;
  unidad: string;
  costo_unitario: string;
  existencia: string;
  stock_minimo: string;
  activo: boolean;
  bajo_minimo: boolean;
}

export interface ProductoInventario {
  id: number;
  codigo: string;
  nombre: string;
  material: string | null;
  peso: string | null;
  existencia: number;
  activo: boolean;
}

export interface MovimientoInsumo {
  id: number;
  insumo_id: number;
  tipo: TipoMovimientoInsumo;
  cantidad: string;
  costo_unitario: string | null;
  proveedor: string | null;
  lote_proveedor: string | null;
  user_id: number;
  ocurrido_en: string | null;
}

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

export interface ReferenciaCatalogo {
  id: number;
  codigo: string;
  nombre: string;
}

export interface LoteParada {
  id: number;
  orden: number;
  area: ReferenciaCatalogo;
  tarifa_mano_obra_hora?: string;
  costo_maquina_hora?: string;
  piezas_recibidas: number | null;
  piezas_entregadas: number | null;
  merma_piezas: number | null;
  merma_material: string | null;
  retorno_kg: string | null;
  discrepancia: number | null;
  entrada_ocurrido_en: string | null;
  entrada_registrado_en: string | null;
  entrada_tardia: boolean;
  salida_ocurrido_en: string | null;
  salida_registrado_en: string | null;
  salida_tardia: boolean;
  admite_retorno: boolean;
  puede_capturar: boolean;
}

export interface RegistrarEntradaParada {
  piezas_recibidas: number;
  ocurrido_en?: string;
}

export interface RegistrarSalidaParada {
  piezas_entregadas: number;
  merma_material?: number;
  retorno_kg?: number;
  ocurrido_en?: string;
}

export interface LoteInsumoAsignado {
  id: number;
  insumo: ReferenciaCatalogo & { unidad: string };
  cantidad: string;
  cantidad_devuelta: string;
  costo_unitario?: string;
}

export interface Lote {
  id: number;
  codigo: string;
  estado: EstadoLote;
  cantidad_objetivo: number;
  piezas_terminadas: number | null;
  producto: ReferenciaCatalogo;
  area_actual: ReferenciaCatalogo | null;
  costo_total?: string;
  costo_por_pieza?: string | null;
  paradas: LoteParada[];
  insumos?: LoteInsumoAsignado[];
  confirmado_en: string | null;
  cancelado_en: string | null;
  motivo_cancelacion: string | null;
  admite_devolucion: boolean;
  created_at: string;
}

export interface DevolucionInsumo {
  insumo_id: number;
  cantidad: number;
}

export interface CancelarLote {
  motivo: string;
  devoluciones?: DevolucionInsumo[];
}

export interface CrearLote {
  producto_id: number;
  cantidad_objetivo: number;
  recorrido?: number[];
}

export interface Usuario {
  id: number;
  name: string;
  email: string;
  rol: Rol | null;
  activo: boolean;
  modulos: Record<string, boolean>;
  areas?: AreaAsignada[];
}

export interface SesionMovil {
  token: string;
  usuario: Usuario;
}

export interface AreaAsignada {
  id: number;
  codigo: string;
  nombre: string;
}

export interface ProductoArea {
  id: number;
  codigo: string;
  nombre: string;
  obligatoria: boolean;
  orden: number;
}

export interface ProductoInsumo {
  insumo_id: number;
  codigo: string;
  nombre: string;
  unidad: string;
  categoria: string;
  cantidad_por_pieza: string;
}

export interface Producto {
  id: number;
  codigo: string;
  nombre: string;
  especificaciones: Record<string, string | number>;
  precio_mayoreo: string;
  precio_menudeo: string;
  existencia: number;
  publicado: boolean;
  activo: boolean;
  recorrido: ProductoArea[];
  receta: ProductoInsumo[];
}

export interface AreaOpcion {
  id: number;
  codigo: string;
  nombre: string;
  obligatoria: boolean;
  orden_catalogo: number;
}

export interface InsumoOpcion {
  id: number;
  codigo: string;
  nombre: string;
  categoria: string;
  unidad: string;
}

export interface OpcionesProducto {
  areas: AreaOpcion[];
  insumos: InsumoOpcion[];
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
  encargados?: Usuario[];
}
