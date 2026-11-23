import { createFetch } from '@vueuse/core';

export const ES_MOVIL = import.meta.env.QUASAR_MODE === 'capacitor';
export const API_URL = import.meta.env.PUBLIC_API_URL;
export const SIN_CONEXION = 'No hay conexión con el servidor';

const conConexion = async (peticion: Promise<Response>): Promise<Response> => {
  try {
    return await peticion;
  } catch {
    throw new Error(SIN_CONEXION);
  }
};

const leerCookie = (nombre: string): string | undefined => {
  const cookie = document.cookie.split('; ').find((c) => c.startsWith(`${nombre}=`));
  return cookie ? decodeURIComponent(cookie.slice(nombre.length + 1)) : undefined;
};

export const cabecerasApi = (base?: HeadersInit): Headers => {
  const headers = new Headers(base);
  headers.set('Accept', 'application/json');
  const xsrf = leerCookie('XSRF-TOKEN');
  if (xsrf) headers.set('X-XSRF-TOKEN', xsrf);
  return headers;
};

export const prepararCsrf = async (): Promise<void> => {
  await conConexion(fetch(`${API_URL}/sanctum/csrf-cookie`, { credentials: 'include' }));
};

export const cabecerasConToken = async (base?: HeadersInit): Promise<Headers> => {
  const headers = cabecerasApi(base);
  if (ES_MOVIL) {
    const { leerToken } = await import('./tokenMovil');
    const token = await leerToken();
    if (token) headers.set('Authorization', `Bearer ${token}`);
  }
  return headers;
};

let sesionPerdida: (() => void) | null = null;

export const alPerderSesion = (accion: () => void): void => {
  sesionPerdida = accion;
};

export const useApi = createFetch({
  baseUrl: `${API_URL}/api`,
  fetchOptions: {
    credentials: ES_MOVIL ? 'omit' : 'include',
  },
  options: {
    updateDataOnError: true,
    async beforeFetch({ options }) {
      options.headers = await cabecerasConToken(options.headers);
      return { options };
    },
    onFetchError(contexto) {
      if (contexto.response?.status === 401) sesionPerdida?.();
      return contexto;
    },
  },
});

export const peticionSesion = async (
  ruta:
    | '/login'
    | '/login/verificar'
    | '/login/reenviar'
    | '/logout'
    | '/forgot-password'
    | '/reset-password'
    | '/forgot-password/pregunta'
    | '/reset-password/pregunta',
  cuerpo?: object,
): Promise<Response> =>
  conConexion(
    fetch(`${API_URL}${ruta}`, {
      method: 'POST',
      credentials: 'include',
      headers: cabecerasApi({ 'content-type': 'application/json' }),
      body: cuerpo ? JSON.stringify(cuerpo) : null,
    }),
  );
