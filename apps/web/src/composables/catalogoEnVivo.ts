import { onMounted, onUnmounted } from 'vue';
import type { CatalogoActualizado, DisponibilidadActualizada } from '@aleacion-prima/types';
import { obtenerEcho } from '../services/echo';

interface Oyentes {
  disponibilidad?: (evento: DisponibilidadActualizada) => void;
  catalogo?: (evento: CatalogoActualizado) => void;
}

export const useCatalogoEnVivo = ({ disponibilidad, catalogo }: Oyentes): void => {
  onMounted(() => {
    const canal = obtenerEcho().channel('catalogo');
    if (disponibilidad) canal.listen('.disponibilidad.actualizada', disponibilidad);
    if (catalogo) canal.listen('.catalogo.actualizado', catalogo);
  });

  onUnmounted(() => {
    const canal = obtenerEcho().channel('catalogo');
    if (disponibilidad) canal.stopListening('.disponibilidad.actualizada', disponibilidad);
    if (catalogo) canal.stopListening('.catalogo.actualizado', catalogo);
  });
};
