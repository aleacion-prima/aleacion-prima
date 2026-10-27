<script setup lang="ts">
import { computed } from 'vue';
import { iconoArea, type EstadoParada } from '@/catalogos/areas';

const props = defineProps<{ area: string; estado: EstadoParada }>();

const color = computed(() =>
  props.estado === 'terminada' ? 'positive' : props.estado === 'en_curso' ? 'accent' : 'grey-3',
);
const colorIcono = computed(() =>
  props.estado === 'terminada' ? 'white' : props.estado === 'en_curso' ? 'dark' : 'grey-7',
);
</script>

<template>
  <q-avatar
    size="24px"
    :color="color"
    :text-color="colorIcono"
    :icon="iconoArea(area)"
    font-size="16px"
    :title="area"
  />
</template>
