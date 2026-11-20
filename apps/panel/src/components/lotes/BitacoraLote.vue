<script setup lang="ts">
import { ref, watch } from 'vue';
import type { EventoBitacora } from '@aleacion-prima/types';
import { ETIQUETAS_EVENTO_BITACORA, ICONOS_EVENTO_BITACORA } from '@/catalogos/bitacora';
import { useApi } from '@/services/api';
import EstadoVacio from '@/components/comunes/EstadoVacio.vue';

const props = defineProps<{ codigo: string; version: number }>();

const eventos = ref<EventoBitacora[]>([]);
const cargando = ref(true);
const fallo = ref(false);

const fecha = new Intl.DateTimeFormat('es-MX', { dateStyle: 'medium', timeStyle: 'short' });

const quien = (evento: EventoBitacora): string =>
  [evento.area?.nombre, evento.usuario]
    .filter((dato) => dato !== null && dato !== undefined)
    .join(' · ');

let solicitud = 0;
let cargadaUnaVez = false;

const cargar = async (): Promise<void> => {
  const propia = ++solicitud;
  const { data, error } = await useApi<EventoBitacora[]>(
    `/lotes/${encodeURIComponent(props.codigo)}/bitacora`,
  ).json<EventoBitacora[]>();
  // Una respuesta vieja que llega tarde no pisa a la más reciente.
  if (propia !== solicitud) return;
  cargando.value = false;
  // Si la recarga en segundo plano falla, se conserva lo que ya se muestra.
  if (error.value) {
    fallo.value = !cargadaUnaVez;
    return;
  }
  cargadaUnaVez = true;
  fallo.value = false;
  eventos.value = data.value ?? [];
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
      <h2 class="text-h6 q-mt-none q-mb-md">Bitácora</h2>

      <div v-if="cargando" class="q-pa-lg flex flex-center">
        <q-spinner color="primary" size="32px" />
      </div>

      <EstadoVacio v-else-if="fallo" icono="cloud_off" titulo="No se pudo cargar la bitácora">
        <q-btn flat color="primary" label="Reintentar" @click="cargar" />
      </EstadoVacio>

      <EstadoVacio v-else-if="eventos.length === 0" icono="history" titulo="Sin eventos todavía" />

      <q-timeline v-else layout="dense" color="primary">
        <q-timeline-entry
          v-for="evento in eventos"
          :key="evento.id"
          :icon="ICONOS_EVENTO_BITACORA[evento.tipo]"
          :color="evento.captura_tardia ? 'warning' : 'primary'"
          :title="ETIQUETAS_EVENTO_BITACORA[evento.tipo]"
          :subtitle="fecha.format(new Date(evento.ocurrido_en))"
        >
          <div>{{ evento.descripcion }}</div>
          <div v-if="quien(evento)" class="text-caption text-secundario">{{ quien(evento) }}</div>
          <q-chip
            v-if="evento.captura_tardia"
            dense
            color="warning"
            text-color="white"
            label="Captura tardía"
            class="q-ml-none"
          />
        </q-timeline-entry>
      </q-timeline>
    </q-card-section>
  </q-card>
</template>
