import { createPinia, setActivePinia } from 'pinia';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import type { Usuario } from '@aleacion-prima/types';

const preferencias = vi.hoisted(() => new Map<string, string>());

vi.mock('@capacitor/preferences', () => ({
  Preferences: {
    get: ({ key }: { key: string }) => Promise.resolve({ value: preferencias.get(key) ?? null }),
    set: ({ key, value }: { key: string; value: string }) => {
      preferencias.set(key, value);
      return Promise.resolve();
    },
    remove: ({ key }: { key: string }) => {
      preferencias.delete(key);
      return Promise.resolve();
    },
  },
}));

vi.mock('@/services/echo', () => ({
  obtenerEcho: vi.fn(() => ({ private: () => ({ listen: vi.fn() }) })),
  desconectarEcho: vi.fn(),
}));

const encargado: Usuario = {
  id: 7,
  name: 'Juan Pérez',
  email: 'juan@aleacionprima.test',
  rol: 'encargado',
  activo: true,
  modulos: { ventas: false },
  areas: [],
};

const respuesta = (status: number, cuerpo?: unknown): Response =>
  new Response(cuerpo === undefined ? null : JSON.stringify(cuerpo), {
    status,
    headers: { 'content-type': 'application/json' },
  });

const fetchFalso = vi.fn<(url: string, init?: RequestInit) => Promise<Response>>();

const llamada = (ruta: string): RequestInit | undefined =>
  fetchFalso.mock.calls.find(([url]) => url.endsWith(ruta))?.[1];

const cabecera = (ruta: string, nombre: string): string | null =>
  new Headers(llamada(ruta)?.headers).get(nombre);

const crearSesion = async (modo: 'capacitor' | 'spa') => {
  vi.stubEnv('QUASAR_MODE', modo);
  vi.resetModules();
  setActivePinia(createPinia());
  const { useSesionStore } = await import('./sesion');
  return useSesionStore();
};

beforeEach(() => {
  preferencias.clear();
  fetchFalso.mockReset();
  vi.stubGlobal('fetch', fetchFalso);
});

afterEach(() => {
  vi.unstubAllEnvs();
  vi.unstubAllGlobals();
});

describe('sesión en la app móvil', () => {
  it('inicia sesión con el token y lo guarda en el dispositivo', async () => {
    fetchFalso.mockResolvedValueOnce(respuesta(201, { token: '1|abc', usuario: encargado }));
    const sesion = await crearSesion('capacitor');

    await sesion.iniciar('juan@aleacionprima.test', 'password');

    expect(JSON.parse(llamada('/api/movil/login')?.body as string)).toEqual({
      email: 'juan@aleacionprima.test',
      password: 'password',
    });
    expect(llamada('/api/movil/login')?.credentials).toBe('omit');
    expect(preferencias.get('token')).toBe('1|abc');
    expect(sesion.usuario).toEqual(encargado);
    expect(sesion.cargada).toBe(true);
  });

  it('muestra el mensaje de la API y no guarda nada si el acceso falla', async () => {
    fetchFalso.mockResolvedValueOnce(
      respuesta(403, { message: 'La app solo es para encargados y administradores.' }),
    );
    const sesion = await crearSesion('capacitor');

    await expect(sesion.iniciar('ventas@aleacionprima.test', 'password')).rejects.toThrow(
      'La app solo es para encargados y administradores.',
    );
    expect(preferencias.has('token')).toBe(false);
    expect(sesion.usuario).toBeNull();
  });

  it('manda el token guardado como Bearer en las peticiones', async () => {
    preferencias.set('token', '1|abc');
    fetchFalso.mockResolvedValueOnce(respuesta(200, encargado));
    const sesion = await crearSesion('capacitor');

    await sesion.cargar();

    expect(cabecera('/api/user', 'Authorization')).toBe('Bearer 1|abc');
    expect(sesion.usuario).toEqual(encargado);
  });

  it('sin token no llama a la API', async () => {
    const sesion = await crearSesion('capacitor');

    await sesion.cargar();

    expect(fetchFalso).not.toHaveBeenCalled();
    expect(sesion.usuario).toBeNull();
    expect(sesion.cargada).toBe(true);
  });

  it('borra el token si la API ya no lo acepta', async () => {
    preferencias.set('token', '1|revocado');
    fetchFalso.mockResolvedValueOnce(respuesta(401, { message: 'Unauthenticated.' }));
    const sesion = await crearSesion('capacitor');

    await sesion.cargar();

    expect(sesion.usuario).toBeNull();
    expect(preferencias.has('token')).toBe(false);
  });

  it('al cerrar sesión revoca el token en la API y lo borra del dispositivo', async () => {
    preferencias.set('token', '1|abc');
    fetchFalso.mockResolvedValueOnce(respuesta(200, encargado));
    fetchFalso.mockResolvedValueOnce(respuesta(204));
    const sesion = await crearSesion('capacitor');
    await sesion.cargar();

    await sesion.cerrar();

    expect(llamada('/api/logout')?.method).toBe('POST');
    expect(cabecera('/api/logout', 'Authorization')).toBe('Bearer 1|abc');
    expect(preferencias.has('token')).toBe(false);
    expect(sesion.usuario).toBeNull();
  });
});

describe('sesión en el panel', () => {
  it('usa la cookie y nunca manda Authorization', async () => {
    preferencias.set('token', '1|abc');
    fetchFalso.mockResolvedValueOnce(respuesta(200, encargado));
    const sesion = await crearSesion('spa');

    await sesion.cargar();

    expect(llamada('/api/user')?.credentials).toBe('include');
    expect(cabecera('/api/user', 'Authorization')).toBeNull();
  });
});
