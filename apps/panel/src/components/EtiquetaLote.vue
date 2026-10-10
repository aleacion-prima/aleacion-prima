<script setup lang="ts">
import { QrcodeSvg } from 'qrcode.vue';

defineProps<{
  codigo: string;
  aleacion?: string;
}>();
</script>

<template>
  <div class="etiqueta">
    <QrcodeSvg :value="codigo" :size="160" level="Q" :margin="4" />
    <strong>{{ codigo }}</strong>
    <span v-if="aleacion">{{ aleacion }}</span>
  </div>
</template>

<style scoped>
@media print {
  .etiqueta {
    break-inside: avoid;
  }
}
</style>
