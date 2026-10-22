<script setup lang="ts">
withDefaults(defineProps<{ titulo: string; descripcion?: string; icono?: string }>(), {
  descripcion: '',
  icono: 'inbox',
});
</script>

<template>
  <div class="column items-center text-center q-pa-xl">
    <q-icon :name="icono" size="48px" class="text-secundario" />
    <div class="text-h6 q-mt-md">{{ titulo }}</div>
    <div v-if="descripcion" class="text-secundario q-mt-xs">{{ descripcion }}</div>
    <div v-if="$slots.default" class="q-mt-md">
      <slot />
    </div>
  </div>
</template>
