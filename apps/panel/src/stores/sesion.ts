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

  const recuperar = async (email: string): Promise<string> => {
    await prepararCsrf();
    const res = await peticionSesion('/forgot-password', { email });
    const cuerpo = (await res.json()) as { message?: string };
    if (!res.ok) throw new Error(cuerpo.message ?? 'No se pudo enviar el enlace');
    return cuerpo.message ?? 'Si el correo existe, te enviamos un enlace.';
  };

  const restablecer = async (datos: {
    token: string;
    email: string;
    password: string;
    password_confirmation: string;
  }): Promise<string> => {
    await prepararCsrf();
    const res = await peticionSesion('/reset-password', datos);
    const cuerpo = (await res.json()) as { message?: string };
    if (!res.ok) throw new Error(cuerpo.message ?? 'No se pudo restablecer la contraseña');
    return cuerpo.message ?? 'Contraseña restablecida.';
  };

  const obtenerPregunta = async (email: string): Promise<string> => {
    await prepararCsrf();
    const res = await peticionSesion('/forgot-password/pregunta', { email });
    const cuerpo = (await res.json()) as { pregunta?: string; message?: string };
    if (!res.ok) throw new Error(cuerpo.message ?? 'No se pudo obtener la pregunta');
    return cuerpo.pregunta ?? '';
  };

  const restablecerPorPregunta = async (datos: {
    email: string;
    respuesta: string;
    password: string;
    password_confirmation: string;
  }): Promise<string> => {
    await prepararCsrf();
    const res = await peticionSesion('/reset-password/pregunta', datos);
    const cuerpo = (await res.json()) as { message?: string };
    if (!res.ok) throw new Error(cuerpo.message ?? 'No se pudo restablecer la contraseña');
    return cuerpo.message ?? 'Contraseña restablecida.';
  };

  return {
    usuario,
    cargada,
    cargar,
    iniciar,
    cerrar,
    recuperar,
    restablecer,
    obtenerPregunta,
    restablecerPorPregunta,
  };
});
