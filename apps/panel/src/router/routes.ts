import type { RouteRecordRaw } from 'vue-router';
import type { Rol } from '@aleacion-prima/types';

declare module 'vue-router' {
  interface RouteMeta {
    requiereSesion?: boolean;
    roles?: Rol[];
  }
}

const routes: RouteRecordRaw[] = [
  {
    path: '/',
    component: () => import('@/layouts/MainLayout.vue'),
    meta: { requiereSesion: true },
    children: [
      { path: '', component: () => import('@/pages/tablero/TableroPage.vue') },
      {
        path: 'areas',
        component: () => import('@/pages/area/AreasPage.vue'),
        meta: { roles: ['administrador'] },
      },
      {
        path: 'usuarios',
        component: () => import('@/pages/usuarios/UsuariosPage.vue'),
        meta: { roles: ['administrador'] },
      },
      {
        path: 'inventario/insumos',
        component: () => import('@/pages/inventario/InsumosPage.vue'),
        meta: { roles: ['administrador', 'ventas'] },
      },
      {
        path: 'inventario/productos',
        component: () => import('@/pages/inventario/ProductosPage.vue'),
        meta: { roles: ['administrador', 'ventas'] },
      },
      {
        path: 'productos',
        component: () => import('@/pages/productos/ProductosPage.vue'),
        meta: { roles: ['administrador'] },
      },
      ...(import.meta.env.DEV
        ? [{ path: 'muestrario', component: () => import('@/pages/sistema/MuestrarioPage.vue') }]
        : []),
    ],
  },
  {
    path: '/login',
    component: () => import('@/layouts/AccesoLayout.vue'),
    children: [{ path: '', component: () => import('@/pages/acceso/LoginPage.vue') }],
  },
  {
    path: '/:catchAll(.*)*',
    component: () => import('@/pages/sistema/ErrorNotFound.vue'),
  },
];

export default routes;
