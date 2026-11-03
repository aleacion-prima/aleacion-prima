import type { Lote, LoteParada } from '@aleacion-prima/types';
import { estadoDeParada, siguienteCaptura } from './recorridoLote';

export interface LoteEnArea {
  lote: Lote;
  parada: LoteParada;
  disponible: boolean;
  anterior: LoteParada | undefined;
}

export interface MovimientoDelDia {
  clave: string;
  hora: string;
  evento: 'Entrada' | 'Salida';
  lote: Lote;
  piezas: number | null;
}

const enCaptura = (lote: Lote): boolean => lote.estado === 'creado' || lote.estado === 'en_proceso';

export const lotesEnArea = (lotes: Lote[], codigo: string): LoteEnArea[] =>
  lotes.filter(enCaptura).flatMap((lote) => {
    const parada = lote.paradas.find(
      (actual) => actual.area.codigo === codigo && estadoDeParada(actual) === 'en_curso',
    );
    if (!parada) return [];
    const anterior = lote.paradas.find((actual) => actual.orden === parada.orden - 1);
    return [{ lote, parada, disponible: parada.puede_capturar, anterior }];
  });

export const lotesPorLlegar = (lotes: Lote[], codigo: string): LoteEnArea[] =>
  lotes.filter(enCaptura).flatMap((lote) => {
    const parada = lote.paradas.find(
      (actual) => actual.area.codigo === codigo && !actual.entrada_ocurrido_en,
    );
    if (!parada) return [];

    const anterior = lote.paradas.find((actual) => actual.orden === parada.orden - 1);
    const siguiente = siguienteCaptura(lote);
    const listo = siguiente?.tipo === 'entrada' && siguiente.parada.id === parada.id;
    const enCamino = anterior !== undefined && estadoDeParada(anterior) === 'en_curso';

    if (!listo && !enCamino) return [];
    return [{ lote, parada, disponible: listo && parada.puede_capturar, anterior }];
  });

const mismoDia = (fecha: Date, hoy: Date): boolean =>
  fecha.getFullYear() === hoy.getFullYear() &&
  fecha.getMonth() === hoy.getMonth() &&
  fecha.getDate() === hoy.getDate();

export const movimientosDeHoy = (lotes: Lote[], codigo: string, hoy: Date): MovimientoDelDia[] =>
  lotes
    .flatMap((lote) =>
      lote.paradas
        .filter((parada) => parada.area.codigo === codigo)
        .flatMap((parada) => {
          const movimientos: MovimientoDelDia[] = [];
          if (parada.entrada_ocurrido_en && mismoDia(new Date(parada.entrada_ocurrido_en), hoy)) {
            movimientos.push({
              clave: `${parada.id}-entrada`,
              hora: parada.entrada_ocurrido_en,
              evento: 'Entrada',
              lote,
              piezas: parada.piezas_recibidas,
            });
          }
          if (parada.salida_ocurrido_en && mismoDia(new Date(parada.salida_ocurrido_en), hoy)) {
            movimientos.push({
              clave: `${parada.id}-salida`,
              hora: parada.salida_ocurrido_en,
              evento: 'Salida',
              lote,
              piezas: parada.piezas_entregadas,
            });
          }
          return movimientos;
        }),
    )
    .sort((a, b) => b.hora.localeCompare(a.hora));
