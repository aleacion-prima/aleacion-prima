import { onMounted, onUnmounted } from 'vue';
import type { DisponibilidadActualizada } from '@aleacion-prima/types';
import { obtenerEcho } from '../services/echo';

export const useDisponibilidadEnVivo = (
  alCambiar: (evento: DisponibilidadActualizada) => void,
): void => {
  onMounted(() => {
    obtenerEcho().channel('catalogo').listen('.disponibilidad.actualizada', alCambiar);
  });

  onUnmounted(() => {
    obtenerEcho().channel('catalogo').stopListening('.disponibilidad.actualizada', alCambiar);
  });
};
