<script setup lang="ts">
import { ref, watch } from 'vue';
import type { HistorialLote, HistorialParada } from '@aleacion-prima/types';
import { formatearCantidad, pzas } from '@/composables/formato';
import { useApi } from '@/services/api';
import EstadoVacio from '@/components/comunes/EstadoVacio.vue';

const props = defineProps<{ codigo: string; version: number }>();

const historial = ref<HistorialLote | null>(null);
const cargando = ref(true);
const fallo = ref(false);

const fecha = new Intl.DateTimeFormat('es-MX', { dateStyle: 'medium', timeStyle: 'short' });

const cuando = (iso: string | null): string => (iso ? fecha.format(new Date(iso)) : '—');

const detalleEntrada = (parada: HistorialParada): string =>
  parada.entrada_ocurrido_en === null
    ? 'Pendiente'
    : [`${pzas(parada.piezas_recibidas ?? 0)} recibidas`, parada.entrada_por]
        .filter((dato) => dato !== null)
        .join(' · ');

const detalleSalida = (parada: HistorialParada): string =>
  parada.salida_ocurrido_en === null
    ? 'Pendiente'
    : [`${pzas(parada.piezas_entregadas ?? 0)} entregadas`, parada.salida_por]
        .filter((dato) => dato !== null)
        .join(' · ');

const merma = (parada: HistorialParada): string => {
  const partes: string[] = [];
  if (parada.merma_piezas !== null) partes.push(pzas(parada.merma_piezas));
  if (parada.merma_material !== null) {
    partes.push(`${formatearCantidad(parada.merma_material)} kg de material`);
  }
  if (parada.retorno_kg !== null)
    partes.push(`${formatearCantidad(parada.retorno_kg)} kg de retorno`);
  return partes.length ? partes.join(' · ') : '—';
};

const diferencia = (parada: HistorialParada): string =>
  parada.discrepancia === null
    ? ''
    : `Diferencia ${parada.discrepancia > 0 ? '+' : ''}${pzas(parada.discrepancia)}`;

let solicitud = 0;

const cargar = async (): Promise<void> => {
  const propia = ++solicitud;
  const { data, error } = await useApi<HistorialLote>(
    `/lotes/${encodeURIComponent(props.codigo)}/historial`,
  ).json<HistorialLote>();
  // Una respuesta vieja que llega tarde no pisa a la más reciente.
  if (propia !== solicitud) return;
  cargando.value = false;
  // Si la recarga en segundo plano falla, se conserva lo que ya se muestra.
  if (error.value) {
    fallo.value = historial.value === null;
    return;
  }
  fallo.value = false;
  historial.value = data.value;
};

watch(
  () => [props.codigo, props.version],
  () => void cargar(),
  { immediate: true },
);
</script>

<template>
  <q-card flat bordered>
    <q-card-section>
      <h2 class="text-h6 q-mt-none q-mb-md">Historial del recorrido</h2>

      <div v-if="cargando" class="q-pa-lg flex flex-center">
        <q-spinner color="primary" size="32px" />
      </div>

      <EstadoVacio v-else-if="fallo" icono="cloud_off" titulo="No se pudo cargar el historial">
        <q-btn flat color="primary" label="Reintentar" @click="cargar" />
      </EstadoVacio>

      <q-markup-table v-else-if="historial" flat separator="horizontal">
        <thead>
          <tr>
            <th class="text-left">Parada</th>
            <th class="text-left">Entrada</th>
            <th class="text-left">Salida</th>
            <th class="text-left">Merma</th>
            <th class="text-left">Marcas</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="parada in historial.paradas" :key="parada.id">
            <td>
              <span class="codigo">{{ parada.orden }} · {{ parada.area.codigo }}</span>
              <div class="text-caption text-secundario">{{ parada.area.nombre }}</div>
            </td>
            <td>
              <div>{{ cuando(parada.entrada_ocurrido_en) }}</div>
              <div class="text-caption text-secundario">{{ detalleEntrada(parada) }}</div>
            </td>
            <td>
              <div>{{ cuando(parada.salida_ocurrido_en) }}</div>
              <div class="text-caption text-secundario">{{ detalleSalida(parada) }}</div>
            </td>
            <td>{{ merma(parada) }}</td>
            <td>
              <q-chip
                v-if="parada.entrada_tardia || parada.salida_tardia"
                dense
                color="warning"
                text-color="white"
                label="Captura tardía"
              />
              <q-chip
                v-if="parada.discrepancia !== null"
                dense
                color="warning"
                text-color="white"
                :label="diferencia(parada)"
              />
              <span
                v-if="
                  !(parada.entrada_tardia || parada.salida_tardia) && parada.discrepancia === null
                "
                class="text-secundario"
              >
                —
              </span>
            </td>
          </tr>
        </tbody>
      </q-markup-table>
    </q-card-section>
  </q-card>
</template>
