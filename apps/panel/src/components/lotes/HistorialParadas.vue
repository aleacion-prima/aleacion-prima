<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { useQuasar } from 'quasar';
import type { HistorialLote, HistorialParada } from '@aleacion-prima/types';
import { formatearCantidad, pzas } from '@/composables/formato';
import { useApi } from '@/services/api';
import EstadoVacio from '@/components/comunes/EstadoVacio.vue';

const props = defineProps<{ codigo: string; version: number; compacto?: boolean }>();

const $q = useQuasar();
const lista = computed(() => props.compacto || $q.screen.lt.sm);

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

const marcas = (parada: HistorialParada): string[] => {
  const etiquetas: string[] = [];
  if (parada.entrada_tardia || parada.salida_tardia) etiquetas.push('Captura tardía');
  if (parada.discrepancia !== null) {
    etiquetas.push(`Diferencia ${parada.discrepancia > 0 ? '+' : ''}${pzas(parada.discrepancia)}`);
  }
  return etiquetas;
};

const momento = (iso: string | null, detalle: string): string =>
  iso ? `${cuando(iso)} · ${detalle}` : detalle;

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
  <q-card flat :bordered="!compacto" :class="{ 'historial--compacto': compacto }">
    <q-card-section :class="{ 'q-pa-none': compacto }">
      <h2 v-if="!compacto" class="text-h6 q-mt-none q-mb-md">Historial del recorrido</h2>

      <div v-if="cargando" class="q-pa-lg flex flex-center">
        <q-spinner color="primary" size="32px" />
      </div>

      <EstadoVacio v-else-if="fallo" icono="cloud_off" titulo="No se pudo cargar el historial">
        <q-btn flat color="primary" label="Reintentar" @click="cargar" />
      </EstadoVacio>

      <ol v-else-if="historial && lista" class="historial-lista">
        <li v-for="parada in historial.paradas" :key="parada.id" class="historial-parada">
          <div class="row items-center q-gutter-x-sm">
            <span class="codigo">{{ parada.orden }} · {{ parada.area.codigo }}</span>
            <span class="text-secundario">{{ parada.area.nombre }}</span>
          </div>
          <dl>
            <div>
              <dt>Entrada</dt>
              <dd>{{ momento(parada.entrada_ocurrido_en, detalleEntrada(parada)) }}</dd>
            </div>
            <div>
              <dt>Salida</dt>
              <dd>{{ momento(parada.salida_ocurrido_en, detalleSalida(parada)) }}</dd>
            </div>
            <div>
              <dt>Merma</dt>
              <dd>{{ merma(parada) }}</dd>
            </div>
          </dl>
          <div v-if="marcas(parada).length" class="q-mt-xs">
            <q-chip
              v-for="marca in marcas(parada)"
              :key="marca"
              dense
              color="warning"
              text-color="white"
              :label="marca"
            />
          </div>
        </li>
      </ol>

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
                v-for="marca in marcas(parada)"
                :key="marca"
                dense
                color="warning"
                text-color="white"
                :label="marca"
              />
              <span v-if="!marcas(parada).length" class="text-secundario">—</span>
            </td>
          </tr>
        </tbody>
      </q-markup-table>
    </q-card-section>
  </q-card>
</template>

<style scoped lang="scss">
.historial--compacto {
  background: transparent;
}

.historial-lista {
  margin: 0;
  padding: 0;
  list-style: none;
}

.historial-parada {
  padding: 12px 0;
  border-bottom: 1px solid $separator-color;
}

.historial-parada:last-child {
  border-bottom: 0;
}

dl {
  margin: 6px 0 0;
}

dl div {
  display: grid;
  grid-template-columns: 64px minmax(0, 1fr);
  gap: 8px;
  padding: 2px 0;
}

dt {
  color: $grey-7;
}

dd {
  margin: 0;
}
</style>
