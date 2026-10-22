import type { RouteRecordRaw } from 'vue-router';

declare module 'vue-router' {
  interface RouteMeta {
    requiereSesion?: boolean;
  }
}

const routes: RouteRecordRaw[] = [
  {
    path: '/',
    component: () => import('@/layouts/MainLayout.vue'),
    meta: { requiereSesion: true },
    children: [
      { path: '', component: () => import('@/pages/tablero/TableroPage.vue') },
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
