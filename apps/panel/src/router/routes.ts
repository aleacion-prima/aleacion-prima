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
        path: 'lotes',
        component: () => import('@/pages/lotes/LotesPage.vue'),
        meta: { roles: ['administrador', 'ventas', 'encargado'] },
      },
      {
        path: 'lotes/nuevo',
        component: () => import('@/pages/lotes/CrearLotePage.vue'),
        meta: { roles: ['administrador'] },
      },
      {
        path: 'lotes/:codigo',
        component: () => import('@/pages/lotes/DetalleLotePage.vue'),
        meta: { roles: ['administrador', 'ventas', 'encargado'] },
      },
      {
        path: 'usuarios',
        component: () => import('@/pages/usuarios/UsuariosPage.vue'),
        meta: { roles: ['administrador'] },
      },
      { path: 'mi-cuenta', component: () => import('@/pages/cuenta/MiCuentaPage.vue') },
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
      {
        path: 'insumos',
        component: () => import('@/pages/insumos/InsumosPage.vue'),
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
    path: '/olvide-contrasena',
    component: () => import('@/layouts/AccesoLayout.vue'),
    children: [{ path: '', component: () => import('@/pages/acceso/OlvideContrasenaPage.vue') }],
  },
  {
    path: '/nueva-contrasena',
    component: () => import('@/layouts/AccesoLayout.vue'),
    children: [{ path: '', component: () => import('@/pages/acceso/NuevaContrasenaPage.vue') }],
  },
  {
    path: '/recuperar-pregunta',
    component: () => import('@/layouts/AccesoLayout.vue'),
    children: [{ path: '', component: () => import('@/pages/acceso/RecuperarPreguntaPage.vue') }],
  },
  {
    path: '/:catchAll(.*)*',
    component: () => import('@/pages/sistema/ErrorNotFound.vue'),
  },
];

export default routes;
