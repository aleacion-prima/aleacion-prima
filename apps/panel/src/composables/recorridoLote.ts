import type { Lote, LoteParada } from '@aleacion-prima/types';
import type { EstadoParada, ParadaLote } from '@/catalogos/areas';

export const estadoDeParada = (parada: LoteParada): EstadoParada => {
  if (parada.salida_ocurrido_en) return 'terminada';
  if (parada.entrada_ocurrido_en) return 'en_curso';
  return 'pendiente';
};

export const paradasDeLote = (lote: Lote): ParadaLote[] =>
  lote.paradas.map((parada) => ({
    codigo: parada.area.codigo,
    nombre: parada.area.nombre,
    estado: estadoDeParada(parada),
    piezas:
      parada.piezas_entregadas !== null
        ? `${parada.piezas_entregadas} pzas entregadas`
        : parada.piezas_recibidas !== null
          ? `${parada.piezas_recibidas} pzas recibidas`
          : '',
  }));

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
