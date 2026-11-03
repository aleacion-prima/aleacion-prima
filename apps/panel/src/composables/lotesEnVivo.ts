import {
  computed,
  onScopeDispose,
  ref,
  toValue,
  watch,
  type MaybeRefOrGetter,
  type Ref,
} from 'vue';
import { useDebounceFn } from '@vueuse/core';
import type { EstadoLote, Lote } from '@aleacion-prima/types';
import { useApi } from '@/services/api';
import { obtenerEcho } from '@/services/echo';
import { useSesionStore } from '@/stores/sesion';

export interface LoteActualizadoEvento {
  codigo: string;
  estado: EstadoLote;
  area_actual_id: number | null;
}

export const useCanalesDeLotes = () => {
  const sesion = useSesionStore();
  return computed(() => {
    const usuario = sesion.usuario;
    if (usuario?.rol === 'administrador') return ['tablero'];
    if (usuario?.rol === 'encargado') return (usuario.areas ?? []).map((area) => `area.${area.id}`);
    return [];
  });
};

export const useLotesEnVivo = (
  canales: MaybeRefOrGetter<string[]>,
  alCambiar: (evento: LoteActualizadoEvento) => void | Promise<void>,
) => {
  const escuchados = new Set<string>();
  const recientes = ref<string[]>([]);

  const marcar = (codigo: string): void => {
    recientes.value = [...recientes.value.filter((actual) => actual !== codigo), codigo];
    setTimeout(() => {
      recientes.value = recientes.value.filter((actual) => actual !== codigo);
    }, 1600);
  };

  const pendientes = new Map<string, LoteActualizadoEvento>();
  const despachar = useDebounceFn(() => {
    const eventos = [...pendientes.values()];
    pendientes.clear();
    eventos.forEach((evento) => {
      marcar(evento.codigo);
      void alCambiar(evento);
    });
  }, 250);

  const recibir = (evento: LoteActualizadoEvento): void => {
    pendientes.set(evento.codigo, evento);
    void despachar();
  };

  const sincronizar = (nuevos: string[]): void => {
    const echo = obtenerEcho();
    for (const canal of [...escuchados]) {
      if (!nuevos.includes(canal)) {
        echo.leave(canal);
        escuchados.delete(canal);
      }
    }
    for (const canal of nuevos) {
      if (!escuchados.has(canal)) {
        echo.private(canal).listen('.lote.actualizado', recibir);
        escuchados.add(canal);
      }
    }
  };

  watch(() => toValue(canales), sincronizar, { immediate: true });

  onScopeDispose(() => {
    const echo = obtenerEcho();
    escuchados.forEach((canal) => echo.leave(canal));
    escuchados.clear();
  });

  return { recientes };
};

export const actualizarEnLista = async (lotes: Ref<Lote[]>, codigo: string): Promise<void> => {
  const { data, error, statusCode } = await useApi<Lote>(
    `/lotes/${encodeURIComponent(codigo)}`,
  ).json<Lote>();

  if (error.value) {
    if (statusCode.value === 404) {
      lotes.value = lotes.value.filter((lote) => lote.codigo !== codigo);
    }
    return;
  }

  const nuevo = data.value;
  if (!nuevo) return;

  const indice = lotes.value.findIndex((lote) => lote.codigo === codigo);
  lotes.value =
    indice === -1
      ? [nuevo, ...lotes.value]
      : lotes.value.map((lote, actual) => (actual === indice ? nuevo : lote));
};
