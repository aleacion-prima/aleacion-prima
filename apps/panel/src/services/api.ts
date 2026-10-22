import { createFetch } from '@vueuse/core';

export const API_URL = import.meta.env.PUBLIC_API_URL;

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
  await fetch(`${API_URL}/sanctum/csrf-cookie`, { credentials: 'include' });
};

export const useApi = createFetch({
  baseUrl: `${API_URL}/api`,
  fetchOptions: {
    credentials: 'include',
  },
  options: {
    updateDataOnError: true,
    beforeFetch({ options }) {
      options.headers = cabecerasApi(options.headers);
      return { options };
    },
  },
});

export const peticionSesion = async (
  ruta: '/login' | '/logout',
  cuerpo?: object,
): Promise<Response> =>
  fetch(`${API_URL}${ruta}`, {
    method: 'POST',
    credentials: 'include',
    headers: cabecerasApi({ 'content-type': 'application/json' }),
    body: cuerpo ? JSON.stringify(cuerpo) : null,
  });
