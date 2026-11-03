<script setup lang="ts">
import { computed } from 'vue';
import { iconoArea, type EstadoParada } from '@/catalogos/areas';

const props = defineProps<{ area: string; estado: EstadoParada }>();

const COLORES: Record<EstadoParada, { fondo: string; icono: string }> = {
  terminada: { fondo: 'positive', icono: 'white' },
  en_curso: { fondo: 'accent', icono: 'dark' },
  cancelada: { fondo: 'negative', icono: 'white' },
  pendiente: { fondo: 'grey-3', icono: 'grey-7' },
  planeada: { fondo: 'grey-3', icono: 'grey-7' },
  omitida: { fondo: 'grey-2', icono: 'grey-5' },
};

const color = computed(() => COLORES[props.estado].fondo);
const colorIcono = computed(() => COLORES[props.estado].icono);
</script>

<template>
  <q-avatar
    size="24px"
    :color="color"
    :text-color="colorIcono"
    :icon="iconoArea(area)"
    font-size="16px"
    :title="area"
    :class="{ 'paso--en-curso': estado === 'en_curso' }"
  />
</template>

<style scoped lang="scss">
.paso--en-curso {
  animation: pulso 1.4s ease-out infinite;
}
</style>
