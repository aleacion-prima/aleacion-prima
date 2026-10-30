import type { Rol } from '@aleacion-prima/types';

export interface ElementoMenu {
  etiqueta: string;
  ruta: string;
  icono: string;
}

export interface SeccionMenu {
  titulo?: string;
  elementos: ElementoMenu[];
}

const principal: SeccionMenu = {
  elementos: [
    { etiqueta: 'Tablero', ruta: '/', icono: 'dashboard' },
    { etiqueta: 'Lotes', ruta: '/lotes', icono: 'precision_manufacturing' },
  ],
};

const inventario: SeccionMenu = {
  titulo: 'Inventario',
  elementos: [
    { etiqueta: 'insumos', ruta: '/inventario/insumos', icono: 'inventory_2' },
    { etiqueta: 'Producto terminado', ruta: '/inventario/productos', icono: 'warehouse' },
  ],
};

const catalogos: SeccionMenu = {
  titulo: 'Catálogos',
  elementos: [
    { etiqueta: 'Áreas y tarifas', ruta: '/areas', icono: 'factory' },
    { etiqueta: 'Productos', ruta: '/productos', icono: 'category' },
    { etiqueta: 'insumos', ruta: '/insumos', icono: 'grain' },
    { etiqueta: 'Usuarios y roles', ruta: '/usuarios', icono: 'manage_accounts' },
  ],
};

const ventas: SeccionMenu = {
  titulo: 'Ventas',
  elementos: [
    { etiqueta: 'Clientes', ruta: '/clientes', icono: 'groups' },
    { etiqueta: 'Pedidos', ruta: '/pedidos', icono: 'receipt_long' },
    { etiqueta: 'Reporte de ventas', ruta: '/reportes/ventas', icono: 'bar_chart' },
    { etiqueta: 'Cotizaciones', ruta: '/cotizaciones', icono: 'request_quote' },
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
        { etiqueta: 'Lotes', ruta: '/lotes', icono: 'precision_manufacturing' },
      ],
    },
    notificaciones,
  ],
  cliente: [{ elementos: [{ etiqueta: 'Cotizaciones', ruta: '/cotizaciones', icono: 'chat' }] }],
};
