<script setup lang="ts">
import { ref } from 'vue';
import { useQuasar } from 'quasar';
import type { Imprevisto, Lote } from '@aleacion-prima/types';
import { ETIQUETAS_TIPO_IMPREVISTO } from '@/catalogos/imprevistos';
import { useApi } from '@/services/api';

const props = defineProps<{ lote: Lote; imprevistos: Imprevisto[] }>();
const emit = defineEmits<{ cambio: [] }>();

const $q = useQuasar();
const resolviendo = ref<string | null>(null);

const fecha = new Intl.DateTimeFormat('es-MX', { dateStyle: 'medium', timeStyle: 'short' });

/** Resuelve el administrador o quien puede capturar en el área del imprevisto. */
const puedeResolver = (imprevisto: Imprevisto): boolean =>
  imprevisto.area !== null &&
  props.lote.paradas.some(
    (parada) => parada.puede_capturar && parada.area.id === imprevisto.area?.id,
  );

const detalle = (imprevisto: Imprevisto): string =>
  [
    imprevisto.maquina,
    imprevisto.piezas_afectadas === null ? null : `${imprevisto.piezas_afectadas} pzas`,
    imprevisto.duracion_minutos === null ? null : `${imprevisto.duracion_minutos} min`,
  ]
    .filter((dato): dato is string => dato !== null && dato !== '')
    .join(' · ');

const resolver = async (imprevisto: Imprevisto): Promise<void> => {
  resolviendo.value = imprevisto.id;
  const { error } = await useApi(
    `/lotes/${encodeURIComponent(props.lote.codigo)}/imprevistos/${imprevisto.id}`,
  )
    .patch({ resuelto: true })
    .json<Imprevisto>();
  resolviendo.value = null;

  if (error.value) {
    $q.notify({ type: 'negative', message: 'No se pudo marcar como resuelto' });
    return;
  }
  $q.notify({ type: 'positive', message: 'Imprevisto marcado como resuelto' });
  emit('cambio');
};
</script>

<template>
  <q-card flat bordered>
    <q-card-section>
      <h2 class="text-h6 q-mt-none q-mb-md">Imprevistos</h2>
      <q-markup-table flat separator="horizontal">
        <thead>
          <tr>
            <th class="text-left">Ocurrió</th>
            <th class="text-left">Tipo</th>
            <th class="text-left">Área</th>
            <th class="text-left">Detalle</th>
            <th class="text-left">Estado</th>
            <th />
          </tr>
        </thead>
        <tbody>
          <tr v-for="imprevisto in imprevistos" :key="imprevisto.id">
            <td class="codigo">{{ fecha.format(new Date(imprevisto.ocurrido_en)) }}</td>
            <td>{{ ETIQUETAS_TIPO_IMPREVISTO[imprevisto.tipo] }}</td>
            <td class="codigo">{{ imprevisto.area?.codigo ?? '—' }}</td>
            <td class="imprevisto__detalle">
              <div>{{ imprevisto.descripcion }}</div>
              <div v-if="detalle(imprevisto)" class="text-caption text-secundario">
                {{ detalle(imprevisto) }}
              </div>
            </td>
            <td>
              <q-chip
                dense
                text-color="white"
                :color="imprevisto.resuelto ? 'positive' : 'warning'"
                :label="imprevisto.resuelto ? 'Resuelto' : 'Abierto'"
                class="q-ma-none"
              />
            </td>
            <td class="text-right">
              <q-btn
                v-if="!imprevisto.resuelto && puedeResolver(imprevisto)"
                flat
                no-caps
                dense
                color="primary"
                label="Marcar como resuelto"
                :loading="resolviendo === imprevisto.id"
                @click="resolver(imprevisto)"
              />
            </td>
          </tr>
        </tbody>
      </q-markup-table>
    </q-card-section>
  </q-card>
</template>

<style scoped lang="scss">
.imprevisto__detalle {
  white-space: normal;
  min-width: 220px;
}
</style>
