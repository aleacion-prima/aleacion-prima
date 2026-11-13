import { defineStore } from 'pinia';
import { ref } from 'vue';
import type { Usuario, SesionMovil } from '@aleacion-prima/types';
import { SIN_CONEXION, peticionSesion, prepararCsrf, useApi } from '@/services/api';
import { desconectarEcho, obtenerEcho } from '@/services/echo';

const ES_MOVIL = import.meta.env.QUASAR_MODE === 'capacitor';

export const useSesionStore = defineStore('sesion', () => {
  const usuario = ref<Usuario | null>(null);
  const cargada = ref<boolean>(false);
  const modulos = ref<Record<string, boolean>>({});
  let canalUsuario: string | null = null;
  let suscritoModulos = false;

  const moduloActivo = (nombre: string): boolean => modulos.value[nombre] === true;

  const suscribirUsuario = (id: number): void => {
    if (canalUsuario) return;
    canalUsuario = `App.Models.User.${id}`;
    obtenerEcho()
      .private(canalUsuario)
      .listen('.usuario.actualizado', () => {
        void cargar();
      });
  };

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
    if (ES_MOVIL) {
      const { leerToken } = await import('@/services/tokenMovil');
      if (!(await leerToken())) {
        usuario.value = null;
        cargada.value = true;
        return;
      }
    }
    const { data, error, statusCode } = await useApi<Usuario>('/user').json<Usuario>();
    usuario.value = error.value ? null : data.value;
    modulos.value = usuario.value?.modulos ?? {};
    cargada.value = true;
    if (ES_MOVIL && statusCode.value === 401) {
      const { borrarToken } = await import('@/services/tokenMovil');
      await borrarToken();
    }
    if (ES_MOVIL) return;
    if (usuario.value) {
      suscribirModulos();
      suscribirUsuario(usuario.value.id);
    } else if (canalUsuario) {
      desconectarEcho();
      suscritoModulos = false;
      canalUsuario = null;
    }
  };

  const iniciar = async (email: string, password: string): Promise<void> => {
    if (ES_MOVIL) {
      const { guardarToken } = await import('@/services/tokenMovil');
      const { data, error, statusCode } = await useApi('/movil/login')
        .post({ email, password })
        .json<Partial<SesionMovil> & { message?: string }>();
      if (error.value && statusCode.value === null) throw new Error(SIN_CONEXION);
      if (error.value || !data.value?.token || !data.value.usuario) {
        throw new Error(data.value?.message ?? 'No se pudo iniciar sesión');
      }
      await guardarToken(data.value.token);
      usuario.value = data.value.usuario;
      modulos.value = usuario.value.modulos ?? {};
      cargada.value = true;
      return;
    }
    await prepararCsrf();
    const res = await peticionSesion('/login', { email, password });
    if (!res.ok) {
      const cuerpo = (await res.json()) as { message?: string };
      throw new Error(cuerpo.message ?? 'No se pudo iniciar sesión');
    }
    await cargar();
  };

  const cerrar = async (): Promise<void> => {
    if (ES_MOVIL) {
      const { borrarToken } = await import('@/services/tokenMovil');
      await useApi('/logout').post();
      await borrarToken();
    } else {
      await peticionSesion('/logout');
      desconectarEcho();
    }
    usuario.value = null;
    modulos.value = {};
    suscritoModulos = false;
    canalUsuario = null;
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
    cerrar,
    recuperar,
    restablecer,
    obtenerPregunta,
    restablecerPorPregunta,
  };
});
