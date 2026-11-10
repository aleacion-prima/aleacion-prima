import type { Lote, LoteParada } from '@aleacion-prima/types';
import type { EstadoParada, ParadaLote } from '@/catalogos/areas';
import { conPlural, pzas } from './formato';

export const estadoDeParada = (parada: LoteParada): EstadoParada => {
  if (parada.salida_ocurrido_en) return 'terminada';
  if (parada.entrada_ocurrido_en) return 'en_curso';
  return 'pendiente';
};

const hora = new Intl.DateTimeFormat('es-MX', {
  hour: '2-digit',
  minute: '2-digit',
  hourCycle: 'h23',
});

const tiempoDeParada = (parada: LoteParada): string => {
  if (!parada.entrada_ocurrido_en) return '';
  const entrada = new Date(parada.entrada_ocurrido_en);
  if (!parada.salida_ocurrido_en) return `Desde ${hora.format(entrada)}`;
  const salida = new Date(parada.salida_ocurrido_en);
  const horas = (salida.getTime() - entrada.getTime()) / 3_600_000;
  return `${hora.format(entrada)} a ${hora.format(salida)} · ${horas.toFixed(1)} h`;
};

const avisoDeParada = (parada: LoteParada): string => {
  const avisos: string[] = [];
  if (parada.discrepancia !== null) {
    avisos.push(`Diferencia ${parada.discrepancia > 0 ? '+' : ''}${pzas(parada.discrepancia)}`);
  }
  if (parada.entrada_tardia || parada.salida_tardia) avisos.push('Captura tardía');
  return avisos.join(' · ');
};

export const estadoVisual = (lote: Lote, parada: LoteParada): EstadoParada => {
  if (lote.estado === 'cancelado') {
    if (parada.salida_ocurrido_en && parada.salida_ocurrido_en === lote.cancelado_en) {
      return 'cancelada';
    }
    if (!parada.entrada_ocurrido_en) return 'omitida';
  }
  return estadoDeParada(parada);
};

export const paradasDeLote = (lote: Lote): ParadaLote[] =>
  lote.paradas.map((parada) => {
    const estado = estadoVisual(lote, parada);
    if (estado === 'cancelada') {
      return {
        codigo: parada.area.codigo,
        nombre: parada.area.nombre,
        estado,
        tiempo: tiempoDeParada(parada),
        aviso: 'Lote cancelado',
        piezas: `${pzas(parada.piezas_recibidas ?? 0)} al cancelar`,
      };
    }
    return {
      codigo: parada.area.codigo,
      nombre: parada.area.nombre,
      estado,
      tiempo: tiempoDeParada(parada),
      aviso: avisoDeParada(parada),
      piezas:
        parada.piezas_entregadas !== null
          ? `${pzas(parada.piezas_entregadas)} ${conPlural(parada.piezas_entregadas, 'entregada', 'entregadas')}`
          : parada.piezas_recibidas !== null
            ? `${pzas(parada.piezas_recibidas)} ${conPlural(parada.piezas_recibidas, 'recibida', 'recibidas')}`
            : '',
    };
  });

export interface SiguienteCaptura {
  parada: LoteParada;
  tipo: 'entrada' | 'salida';
}

export const siguienteCaptura = (lote: Lote): SiguienteCaptura | null => {
  if (lote.estado !== 'creado' && lote.estado !== 'en_proceso') return null;

  const enCurso = lote.paradas.find((parada) => estadoDeParada(parada) === 'en_curso');
  if (enCurso) return { parada: enCurso, tipo: 'salida' };

  const pendiente = lote.paradas.find((parada) => !parada.entrada_ocurrido_en);
  return pendiente ? { parada: pendiente, tipo: 'entrada' } : null;
};

export const resumenRecorrido = (lote: Lote): string => {
  const total = lote.paradas.length;
  const terminadas = lote.paradas.filter((parada) => parada.salida_ocurrido_en).length;
  const enCurso = lote.paradas.find((parada) => estadoDeParada(parada) === 'en_curso');

  if (lote.estado === 'cancelado') {
    const interrumpida = lote.paradas.find((parada) => estadoVisual(lote, parada) === 'cancelada');
    const completadas = lote.paradas.filter(
      (parada) => estadoVisual(lote, parada) === 'terminada',
    ).length;
    const donde = interrumpida ?? lote.paradas[completadas - 1];
    return donde
      ? `Cancelado en ${donde.area.nombre} · ${completadas} de ${total}`
      : `Cancelado sin iniciar · 0 de ${total}`;
  }
  if (enCurso) return `${enCurso.area.nombre} · ${terminadas} de ${total}`;
  if (terminadas === 0) return `Sin iniciar · 0 de ${total}`;

  const ultima = lote.paradas[terminadas - 1];
  return `${ultima?.area.nombre ?? ''} · ${terminadas} de ${total}`;
};
