<script setup lang="ts">
import { computed } from 'vue';
import type { Semaforo } from '@aleacion-prima/types';
import { ESTILOS_SEMAFORO } from '@/catalogos/semaforo';

const props = defineProps<{ semaforo: Semaforo | null; motivos: string[] }>();

const estilo = computed(() => (props.semaforo === null ? null : ESTILOS_SEMAFORO[props.semaforo]));

/** El color solo no basta: el lector de pantalla recibe el nombre y los motivos. */
const descripcion = computed(() => {
  if (estilo.value === null) return '';
  return props.motivos.length > 0
    ? `${estilo.value.etiqueta}: ${props.motivos.join(' ')}`
    : estilo.value.etiqueta;
});
</script>

<template>
  <span
    v-if="estilo"
    class="punto-semaforo"
    :class="`bg-${estilo.color}`"
    role="img"
    :aria-label="descripcion"
    :data-semaforo="semaforo"
  >
    <q-tooltip anchor="top middle" self="bottom middle" max-width="320px">
      <div class="text-weight-medium">{{ estilo.etiqueta }}</div>
      <div v-if="motivos.length === 0">Sin alertas</div>
      <div v-for="motivo in motivos" :key="motivo">{{ motivo }}</div>
    </q-tooltip>
  </span>
</template>

<style scoped lang="scss">
.punto-semaforo {
  display: inline-block;
  flex-shrink: 0;
  align-self: center;
  width: 12px;
  height: 12px;
  border-radius: 50%;
  box-shadow:
    0 0 0 2px #fff,
    0 0 0 3px rgba(15, 23, 42, 0.2);
}
</style>
