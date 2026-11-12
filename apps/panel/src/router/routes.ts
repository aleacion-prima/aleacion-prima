import type { RouteRecordRaw } from 'vue-router';
import type { Rol } from '@aleacion-prima/types';
import rutasMovil from './rutasMovil';
import rutasPanel from './rutasPanel';

declare module 'vue-router' {
  interface RouteMeta {
    requiereSesion?: boolean;
    roles?: Rol[];
    // Si se define, la ruta solo es accesible con ese módulo activo.
    modulo?: string;
  }
}

const routes: RouteRecordRaw[] =
  import.meta.env.QUASAR_MODE === 'capacitor' ? rutasMovil : rutasPanel;

export default routes;
