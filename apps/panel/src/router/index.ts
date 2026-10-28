import { defineRouter } from '#q-app';
import {
  createMemoryHistory,
  createRouter,
  createWebHashHistory,
  createWebHistory,
} from 'vue-router';
import { useSesionStore } from '@/stores/sesion';

import routes from './routes';

export default defineRouter(({ store }) => {
  const createHistory = import.meta.env.QUASAR_SERVER
    ? createMemoryHistory
    : import.meta.env.QUASAR_VUE_ROUTER_MODE === 'history'
      ? createWebHistory
      : createWebHashHistory;

  const Router = createRouter({
    scrollBehavior: () => ({ left: 0, top: 0 }),
    routes,
    history: createHistory(import.meta.env.QUASAR_VUE_ROUTER_BASE),
  });

  Router.beforeEach(async (to) => {
    const sesion = useSesionStore(store);
    if (!sesion.cargada) await sesion.cargar();
    if (to.meta.roles && (!sesion.usuario?.rol || !to.meta.roles.includes(sesion.usuario.rol))) {
      return '/';
    }
    if (to.meta.requiereSesion && !sesion.usuario) {
      return { path: '/login', query: { volver: to.fullPath } };
    }
    if (to.path === '/login' && sesion.usuario) return '/';
    return true;
  });

  return Router;
});
