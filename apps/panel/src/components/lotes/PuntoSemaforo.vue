<script setup lang="ts">
import { computed } from 'vue';
import type { Semaforo } from '@aleacion-prima/types';
import { ESTILOS_SEMAFORO } from '@/catalogos/semaforo';

const props = withDefaults(
  defineProps<{
    semaforo: Semaforo | null;
    motivos: string[];
    /** Una señal no se pudo leer: el color puede ser más benigno que el real. */
    incompleto?: boolean;
  }>(),
  { incompleto: false },
);

const estilo = computed(() => (props.semaforo === null ? null : ESTILOS_SEMAFORO[props.semaforo]));

/** El color solo no basta: el lector de pantalla recibe el nombre y los motivos. */
const descripcion = computed(() => {
  if (estilo.value === null) return '';
  const base =
    props.motivos.length > 0
      ? `${estilo.value.etiqueta}: ${props.motivos.join(' ')}`
      : estilo.value.etiqueta;
  return props.incompleto
    ? `${base} Datos incompletos: no se pudieron leer los imprevistos.`
    : base;
});
</script>

<template>
  <span
    v-if="estilo"
    class="punto-semaforo"
    :class="[`bg-${estilo.color}`, { 'punto-semaforo--incompleto': incompleto }]"
    role="img"
    :aria-label="descripcion"
    :data-semaforo="semaforo"
  >
    <q-tooltip anchor="top middle" self="bottom middle" max-width="320px">
      <div class="text-weight-medium">{{ estilo.etiqueta }}</div>
      <div v-if="motivos.length === 0">Sin alertas</div>
      <div v-for="motivo in motivos" :key="motivo">{{ motivo }}</div>
      <div v-if="incompleto" class="q-mt-xs">
        No se pudieron leer los imprevistos: el semáforo puede estar incompleto.
      </div>
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

  &--incompleto {
    outline: 2px dashed rgba(15, 23, 42, 0.55);
    outline-offset: 3px;
  }
}
</style>
