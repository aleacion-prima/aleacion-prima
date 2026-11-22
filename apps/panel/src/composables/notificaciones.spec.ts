import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

// Respuesta paginada mínima que devuelve GET /api/notificaciones?no_leidas=1
const respuestaPaginadaVacia = {
  data: [],
  meta: { total: 0, per_page: 15, current_page: 1, last_page: 1 },
};

const respuestaPaginadaUna = {
  data: [
    {
      id: '1',
      tipo: 'stock_minimo',
      mensaje: 'Sin chatarra',
      codigo: null,
      leida: false,
      fecha: '2026-11-03T10:00:00Z',
    },
  ],
  meta: { total: 1, per_page: 15, current_page: 1, last_page: 1 },
};

// Mock de useApi: createFetch de @vueuse/core. json() devuelve { data, error }.
const useApiMock = vi.fn(() => ({
  json: vi.fn(),
}));

vi.mock('@/services/api', () => ({
  useApi: useApiMock,
}));

const echo = vi.hoisted(() => ({
  canales: new Map<string, { notificationCb: ((n: unknown) => void) | null }>(),
  leave: vi.fn((canal: string) => void echo.canales.delete(canal)),
}));

vi.mock('@/services/echo', () => ({
  obtenerEcho: () => ({
    private: (canal: string) => {
      if (!echo.canales.has(canal)) {
        echo.canales.set(canal, { notificationCb: null });
      }
      const entrada = echo.canales.get(canal)!;
      return {
        notification: (cb: (n: unknown) => void) => {
          entrada.notificationCb = cb;
        },
      };
    },
    leave: echo.leave,
  }),
  desconectarEcho: vi.fn(),
}));

beforeEach(() => {
  echo.canales.clear();
  echo.leave.mockReset();
  useApiMock.mockReset();
  // Por defecto, la API responde vacía para que cargarPendientes() no falle.
  useApiMock.mockReturnValue({
    json: vi.fn().mockResolvedValue({
      data: { value: respuestaPaginadaVacia },
      error: { value: null },
    }),
  });
});

afterEach(() => {
  vi.restoreAllMocks();
});

const crearComposable = async () => {
  const { useNotificaciones } = await import('@/composables/notificaciones');
  return useNotificaciones();
};

describe('useNotificaciones (campana)', () => {
  it('se suscribe al canal privado del administrador al iniciar', async () => {
    const { pendientes, iniciar } = await crearComposable();
    await iniciar(11, 'administrador');

    expect([...echo.canales.keys()]).toContain('App.Models.User.11');
    expect(pendientes.value).toBe(0);
  });

  it('no se suscribe si el rol no es administrador', async () => {
    const { pendientes, iniciar } = await crearComposable();
    await iniciar(12, 'ventas');

    expect(echo.canales.size).toBe(0);
    expect(pendientes.value).toBe(0);
  });

  it('no se suscribe si el rol es null', async () => {
    const { pendientes, iniciar } = await crearComposable();
    await iniciar(13, null);

    expect(echo.canales.size).toBe(0);
    expect(pendientes.value).toBe(0);
  });

  it('sube el contador cuando llega una notificación sin recargar', async () => {
    const { pendientes, iniciar } = await crearComposable();
    await iniciar(14, 'administrador');

    const entrada = echo.canales.get('App.Models.User.14')!;
    expect(entrada.notificationCb).toBeInstanceOf(Function);

    entrada.notificationCb!({ data: { tipo: 'stock_minimo' } });
    expect(pendientes.value).toBe(1);

    entrada.notificationCb!({ data: { tipo: 'lote_por_confirmar' } });
    expect(pendientes.value).toBe(2);
  });

  it('no sube el contador si la notificación no tiene data.tipo', async () => {
    const { pendientes, iniciar } = await crearComposable();
    await iniciar(15, 'administrador');

    const entrada = echo.canales.get('App.Models.User.15')!;
    entrada.notificationCb!({});
    expect(pendientes.value).toBe(0);
  });

  it('desconectar resetea el contador sin tocar la conexión', async () => {
    const { pendientes, iniciar, desconectar } = await crearComposable();
    await iniciar(16, 'administrador');

    const entrada = echo.canales.get('App.Models.User.16')!;
    entrada.notificationCb!({ data: { tipo: 'stock_minimo' } });
    expect(pendientes.value).toBe(1);
    echo.leave.mockClear();

    desconectar();

    expect(pendientes.value).toBe(0);
    expect(echo.leave).not.toHaveBeenCalled();
  });

  it('carga los pendientes iniciales desde la API al iniciar', async () => {
    useApiMock.mockReturnValue({
      json: vi.fn().mockResolvedValue({
        data: { value: respuestaPaginadaUna },
        error: { value: null },
      }),
    });
    const { pendientes, iniciar } = await crearComposable();
    await iniciar(17, 'administrador');

    expect(pendientes.value).toBe(1);
  });
});
