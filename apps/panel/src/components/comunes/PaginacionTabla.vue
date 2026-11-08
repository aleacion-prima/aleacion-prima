<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
  defineProps<{ pagina: number; porPagina: number; total: number; sustantivo?: string }>(),
  { sustantivo: 'registros' },
);

const emit = defineEmits<{ 'update:pagina': [pagina: number] }>();

const paginas = computed(() => Math.max(Math.ceil(props.total / props.porPagina), 1));
const desde = computed(() => (props.total ? (props.pagina - 1) * props.porPagina + 1 : 0));
const hasta = computed(() => Math.min(props.pagina * props.porPagina, props.total));
</script>

<template>
  <div class="paginacion row items-center justify-between full-width">
    <span class="text-caption text-secundario">
      {{ desde }}–{{ hasta }} de {{ total }} {{ sustantivo }}
    </span>
    <q-pagination
      v-if="paginas > 1"
      :model-value="pagina"
      :max="paginas"
      :max-pages="5"
      direction-links
      flat
      color="grey-8"
      active-color="primary"
      active-design="unelevated"
      @update:model-value="emit('update:pagina', $event)"
    />
  </div>
</template>
