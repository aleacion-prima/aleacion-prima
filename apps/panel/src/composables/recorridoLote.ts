import type { Lote, LoteParada } from '@aleacion-prima/types';
import type { EstadoParada, ParadaLote } from '@/catalogos/areas';

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
    avisos.push(`Diferencia ${parada.discrepancia > 0 ? '+' : ''}${parada.discrepancia} pzas`);
  }
  if (parada.entrada_tardia || parada.salida_tardia) avisos.push('Captura tardía');
  return avisos.join(' · ');
};

export const paradasDeLote = (lote: Lote): ParadaLote[] =>
  lote.paradas.map((parada) => ({
    codigo: parada.area.codigo,
    nombre: parada.area.nombre,
    estado: estadoDeParada(parada),
    tiempo: tiempoDeParada(parada),
    aviso: avisoDeParada(parada),
    piezas:
      parada.piezas_entregadas !== null
        ? `${parada.piezas_entregadas} pzas entregadas`
        : parada.piezas_recibidas !== null
          ? `${parada.piezas_recibidas} pzas recibidas`
          : '',
  }));

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
    const ultima = enCurso ?? lote.paradas[Math.max(terminadas - 1, 0)];
    return `Cancelado en ${ultima?.area.nombre ?? 'creación'} · ${terminadas} de ${total}`;
  }
  if (enCurso) return `${enCurso.area.nombre} · ${terminadas} de ${total}`;
  if (terminadas === 0) return `Sin iniciar · 0 de ${total}`;

  const ultima = lote.paradas[terminadas - 1];
  return `${ultima?.area.nombre ?? ''} · ${terminadas} de ${total}`;
};
