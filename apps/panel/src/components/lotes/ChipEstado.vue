<template>
  <q-chip
    :color="estilo.color"
    :text-color="estilo.colorTexto"
    dense
    class="text-weight-medium"
    :label="estilo.etiqueta"
  />
</template>

<script setup lang="ts">
import { computed } from 'vue';
import type { EstadoLote } from '@aleacion-prima/types';
import { ESTILOS_ESTADO_LOTE } from '@/catalogos/estadosLote';

const props = defineProps<{ estado: EstadoLote }>();

const estilo = computed(() => ESTILOS_ESTADO_LOTE[props.estado]);
</script>
