import { ref } from 'vue';
import type { Notificacion, Rol } from '@aleacion-prima/types';
import { useApi } from '@/services/api';
import { obtenerEcho } from '@/services/echo';

interface RespuestaPaginada<T> {
  data: T[];
  meta: { current_page: number; last_page: number; per_page: number; total: number };
}

interface NotificacionTransmitida {
  id?: string;
  data?: Partial<Notificacion>;
}

const pendientes = ref(0);
let canal: string | null = null;

const suscribir = (id: number, rol: Rol | null): void => {
  if (rol !== 'administrador' || canal === `App.Models.User.${id}`) return;
  if (canal) obtenerEcho().leave(canal);
  canal = `App.Models.User.${id}`;
  obtenerEcho()
    .private(canal)
    .notification((notificacion: NotificacionTransmitida) => {
      if (notificacion.data?.tipo) pendientes.value += 1;
    });
};

const cargarPendientes = async (): Promise<void> => {
  const respuesta = await useApi<RespuestaPaginada<Notificacion>>(
    '/notificaciones?no_leidas=1',
  ).json<RespuestaPaginada<Notificacion>>();
  if (!respuesta.error.value) pendientes.value = respuesta.data.value?.meta.total ?? 0;
};

export const useNotificaciones = () => {
  const iniciar = async (id: number, rol: Rol | null): Promise<void> => {
    if (rol !== 'administrador') return;
    suscribir(id, rol);
    await cargarPendientes();
  };

  const actualizar = (cantidad: number): void => {
    pendientes.value = Math.max(0, cantidad);
  };

  const desconectar = (): void => {
    canal = null;
    pendientes.value = 0;
  };

  return { pendientes, iniciar, actualizar, desconectar };
};
