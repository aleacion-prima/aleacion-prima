import type { Rol } from '@aleacion-prima/types';

export interface ElementoMenu {
  etiqueta: string;
  ruta: string;
  icono?: string;
}

export interface SeccionMenu {
  titulo?: string;
  elementos: ElementoMenu[];
}

const principal: SeccionMenu = {
  elementos: [
    { etiqueta: 'Tablero', ruta: '/', icono: 'dashboard' },
    { etiqueta: 'Lotes', ruta: '/lotes', icono: 'inventory_2' },
  ],
};

const inventario: SeccionMenu = {
  titulo: 'Inventario',
  elementos: [
    { etiqueta: 'Insumos', ruta: '/inventario/insumos' },
    { etiqueta: 'Producto terminado', ruta: '/inventario/productos' },
  ],
};

const catalogos: SeccionMenu = {
  titulo: 'Catálogos',
  elementos: [
    { etiqueta: 'Áreas y tarifas', ruta: '/areas' },
    { etiqueta: 'Productos', ruta: '/productos' },
    { etiqueta: 'Insumos', ruta: '/insumos' },
    { etiqueta: 'Usuarios y roles', ruta: '/usuarios' },
  ],
};

const ventas: SeccionMenu = {
  titulo: 'Ventas',
  elementos: [
    { etiqueta: 'Clientes', ruta: '/clientes' },
    { etiqueta: 'Pedidos', ruta: '/pedidos' },
    { etiqueta: 'Reporte de ventas', ruta: '/reportes/ventas' },
    { etiqueta: 'Cotizaciones', ruta: '/cotizaciones' },
  ],
};

const notificaciones: SeccionMenu = {
  elementos: [{ etiqueta: 'Notificaciones', ruta: '/notificaciones', icono: 'notifications' }],
};

export const MENU_POR_ROL: Record<Rol, SeccionMenu[]> = {
  administrador: [principal, inventario, catalogos, notificaciones, ventas],
  ventas: [principal, inventario, notificaciones, ventas],
  encargado: [
    {
      elementos: [
        { etiqueta: 'Mi área', ruta: '/', icono: 'handyman' },
        { etiqueta: 'Lotes', ruta: '/lotes', icono: 'inventory_2' },
      ],
    },
    notificaciones,
  ],
  cliente: [{ elementos: [{ etiqueta: 'Cotizaciones', ruta: '/cotizaciones', icono: 'chat' }] }],
};
