import type { RouteRecordRaw } from 'vue-router';

const rutasMovil: RouteRecordRaw[] = [
  {
    path: '/',
    component: () => import('@/layouts/AccesoLayout.vue'),
    meta: { requiereSesion: true },
    children: [{ path: '', component: () => import('@/pages/movil/InicioMovilPage.vue') }],
  },
  {
    path: '/login',
    component: () => import('@/layouts/AccesoLayout.vue'),
    children: [{ path: '', component: () => import('@/pages/movil/AccesoMovilPage.vue') }],
  },
  { path: '/:catchAll(.*)*', redirect: '/' },
];

export default rutasMovil;
