<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import { TransitionPresets, usePreferredReducedMotion, useTransition } from '@vueuse/core';
import { formatearCantidad, formatearMoneda } from '@/composables/formato';

const props = withDefaults(
  defineProps<{
    valor: number | string;
    formato?: 'cantidad' | 'moneda' | 'entero';
    decimales?: number;
  }>(),
  { formato: 'cantidad', decimales: 3 },
);

const movimiento = usePreferredReducedMotion();
const objetivo = computed(() => Number(props.valor) || 0);
const fuente = ref(0);

const animado = useTransition(fuente, {
  duration: 500,
  easing: TransitionPresets.easeOutCubic,
  disabled: computed(() => movimiento.value === 'reduce'),
});

const texto = computed(() => {
  if (props.formato === 'moneda') return formatearMoneda(animado.value);
  if (props.formato === 'entero') return String(Math.round(animado.value));
  return formatearCantidad(animado.value, props.decimales);
});

watch(objetivo, (valor) => {
  fuente.value = valor;
});

onMounted(() => {
  fuente.value = objetivo.value;
});
</script>

<template>
  <span class="numero-animado">{{ texto }}</span>
</template>

<style scoped>
.numero-animado {
  font-variant-numeric: tabular-nums;
}
</style>
