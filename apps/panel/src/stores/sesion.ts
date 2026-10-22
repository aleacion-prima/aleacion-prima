import { defineStore } from 'pinia';
import { ref } from 'vue';
import type { Usuario } from '@aleacion-prima/types';
import { peticionSesion, prepararCsrf, useApi } from '@/services/api';
import { desconectarEcho } from '@/services/echo';

export const useSesionStore = defineStore('sesion', () => {
  const usuario = ref<Usuario | null>(null);
  const cargada = ref<boolean>(false);

  const cargar = async (): Promise<void> => {
    const { data, error } = await useApi<Usuario>('/user').json<Usuario>();
    usuario.value = error.value ? null : data.value;
    cargada.value = true;
  };

  const iniciar = async (email: string, password: string): Promise<void> => {
    await prepararCsrf();
    const res = await peticionSesion('/login', { email, password });
    if (!res.ok) {
      const cuerpo = (await res.json()) as { message?: string };
      throw new Error(cuerpo.message ?? 'No se pudo iniciar sesión');
    }
    await cargar();
  };

  const cerrar = async (): Promise<void> => {
    await peticionSesion('/logout');
    desconectarEcho();
    usuario.value = null;
  };

  return {
    usuario,
    cargada,
    cargar,
    iniciar,
    cerrar,
  };
});
