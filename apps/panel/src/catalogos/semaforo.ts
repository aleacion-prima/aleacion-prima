import type { Semaforo } from '@aleacion-prima/types';

export interface EstiloSemaforo {
  /** Nombre del color de Quasar (`$positive`, `$warning`, `$negative`). */
  color: string;
  etiqueta: string;
}

/** Colores semánticos de docs/diseno.md. El semáforo es aparte del chip de estado. */
export const ESTILOS_SEMAFORO: Record<Semaforo, EstiloSemaforo> = {
  verde: { color: 'positive', etiqueta: 'Semáforo verde' },
  ambar: { color: 'accent', etiqueta: 'Semáforo ámbar' },
  rojo: { color: 'negative', etiqueta: 'Semáforo rojo' },
};
