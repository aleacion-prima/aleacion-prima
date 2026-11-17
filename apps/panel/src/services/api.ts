import { createFetch } from '@vueuse/core';

const ES_MOVIL = import.meta.env.QUASAR_MODE === 'capacitor';
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

export const useApi = createFetch({
  baseUrl: `${API_URL}/api`,
  fetchOptions: {
    credentials: ES_MOVIL ? 'omit' : 'include',
  },
  options: {
    updateDataOnError: true,
    async beforeFetch({ options }) {
      const headers = cabecerasApi(options.headers);
      if (ES_MOVIL) {
        const { leerToken } = await import('./tokenMovil');
        const token = await leerToken();
        if (token) headers.set('Authorization', `Bearer ${token}`);
      }
      options.headers = headers;
      return { options };
    },
  },
});

export const peticionSesion = async (
  ruta:
    | '/login'
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
