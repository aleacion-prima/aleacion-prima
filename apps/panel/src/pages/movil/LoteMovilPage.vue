<script setup lang="ts">
import { computed } from 'vue';
import { useRoute } from 'vue-router';

const route = useRoute();
const codigo = computed(() => String(route.params.codigo ?? ''));
</script>

<template>
  <q-page class="flex flex-center q-pa-lg">
    <div class="text-h6 codigo">{{ codigo }}</div>
  </q-page>
</template>
