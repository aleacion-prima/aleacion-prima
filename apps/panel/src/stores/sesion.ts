import { defineStore } from 'pinia';
import { ref } from 'vue';
import type { Usuario } from '@aleacion-prima/types';
import { peticionSesion, prepararCsrf, useApi } from '@/services/api';
import { desconectarEcho, obtenerEcho } from '@/services/echo';

export const useSesionStore = defineStore('sesion', () => {
  const usuario = ref<Usuario | null>(null);
  const cargada = ref<boolean>(false);
  const modulos = ref<Record<string, boolean>>({});
  let suscritoModulos = false;

  const moduloActivo = (nombre: string): boolean => modulos.value[nombre] === true;

  // Escucha en vivo los cambios de módulos (canal privado, tras el login).
  const suscribirModulos = (): void => {
    if (suscritoModulos) return;
    suscritoModulos = true;
    obtenerEcho()
      .private('modulos')
      .listen('.modulos.actualizados', (e: { modulos: Record<string, boolean> }) => {
        modulos.value = e.modulos;
      });
  };

  const cargar = async (): Promise<void> => {
    const { data, error } = await useApi<Usuario>('/user').json<Usuario>();
    usuario.value = error.value ? null : data.value;
    modulos.value = usuario.value?.modulos ?? {};
    cargada.value = true;
    if (usuario.value) suscribirModulos();
  };

  // Devuelve true si el usuario tiene segundo factor: la sesión queda pendiente
  // y hay que verificar el código antes de entrar.
  const iniciar = async (email: string, password: string): Promise<boolean> => {
    await prepararCsrf();
    const res = await peticionSesion('/login', { email, password });
    if (!res.ok) {
      const cuerpo = (await res.json()) as { message?: string };
      throw new Error(cuerpo.message ?? 'No se pudo iniciar sesión');
    }
    // Sin 2FA el login responde 204 (ya entró). Con 2FA responde 200 con
    // { segundo_factor: true } y la sesión queda pendiente de verificar.
    if (res.status !== 204) {
      const cuerpo = (await res.json()) as { segundo_factor?: boolean };
      if (cuerpo.segundo_factor) return true;
    }
    await cargar();
    return false;
  };

  // Verifica el código del segundo factor y, si es correcto, inicia la sesión.
  const verificarCodigo = async (codigo: string): Promise<void> => {
    await prepararCsrf();
    const res = await peticionSesion('/login/verificar', { codigo });
    if (!res.ok) {
      const cuerpo = (await res.json()) as { message?: string; errors?: Record<string, string[]> };
      throw new Error(
        cuerpo.errors?.codigo?.[0] ?? cuerpo.message ?? 'No se pudo verificar el código',
      );
    }
    await cargar();
  };

  const reenviarCodigo = async (): Promise<string> => {
    await prepararCsrf();
    const res = await peticionSesion('/login/reenviar');
    const cuerpo = (await res.json()) as { message?: string };
    if (!res.ok) throw new Error(cuerpo.message ?? 'No se pudo reenviar el código');
    return cuerpo.message ?? 'Te enviamos un código nuevo.';
  };

  const cerrar = async (): Promise<void> => {
    await peticionSesion('/logout');
    desconectarEcho();
    usuario.value = null;
    modulos.value = {};
    suscritoModulos = false;
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
    modulos,
    moduloActivo,
    cargar,
    iniciar,
    verificarCodigo,
    reenviarCodigo,
    cerrar,
    recuperar,
    restablecer,
    obtenerPregunta,
    restablecerPorPregunta,
  };
});
