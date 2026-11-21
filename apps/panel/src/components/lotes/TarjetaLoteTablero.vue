<script setup lang="ts">
import { computed } from 'vue';
import type { Lote } from '@aleacion-prima/types';
import { formatearMoneda, pzas } from '@/composables/formato';
import { paradasDeLote } from '@/composables/recorridoLote';
import ChipEstado from './ChipEstado.vue';
import PuntoSemaforo from './PuntoSemaforo.vue';
import RecorridoLote from './RecorridoLote.vue';

const props = defineProps<{ lote: Lote }>();

const costo = computed(() => {
  if (props.lote.costo_total === undefined) return '';
  const monto = formatearMoneda(props.lote.costo_total);
  return props.lote.estado === 'por_confirmar'
    ? `Costo final ${monto} · espera confirmación`
    : `Costo a la fecha ${monto}`;
});
</script>

<template>
  <q-card flat bordered class="tarjeta-tablero">
    <router-link :to="`/lotes/${lote.codigo}`" class="tarjeta-tablero__encabezado">
      <span class="tarjeta-tablero__codigo">
        <PuntoSemaforo
          :semaforo="lote.semaforo"
          :motivos="lote.motivos"
          :incompleto="lote.semaforo_incompleto ?? false"
        />
        <span class="text-h6 codigo">{{ lote.codigo }}</span>
      </span>
      <span>{{ lote.producto.nombre }}</span>
      <span class="text-secundario">{{ pzas(lote.cantidad_objetivo) }}</span>
      <q-space />
      <span v-if="costo" class="text-secundario text-caption">{{ costo }}</span>
      <ChipEstado :estado="lote.estado" />
    </router-link>
    <RecorridoLote :paradas="paradasDeLote(lote)" />
  </q-card>
</template>

<style scoped lang="scss">
.tarjeta-tablero {
  padding: 20px 24px 8px;
  border-radius: 8px;
}

.tarjeta-tablero__encabezado {
  display: flex;
  flex-wrap: wrap;
  align-items: baseline;
  gap: 4px 12px;
  margin-bottom: 8px;
  color: inherit;
  text-decoration: none;
}

.tarjeta-tablero__codigo {
  display: inline-flex;
  align-items: baseline;
  gap: 10px;
}
</style>
