<script setup lang="ts">
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import { useSesionStore } from '@/stores/sesion';
import PanelDebug from './PanelDebug.vue';

const emit = defineEmits<{ actualizado: [] }>();

const route = useRoute();
const sesion = useSesionStore();
const codigo = computed(() => /^\/lotes\/(LOT-[^/]+)$/.exec(route.path)?.[1] ?? null);
</script>

<template>
  <q-page-sticky position="bottom-right" :offset="[18, 18]">
    <q-btn fab-mini color="dark" icon="bug_report" aria-label="Modo debug">
      <q-menu anchor="top right" self="bottom right" :offset="[0, 8]">
        <PanelDebug
          :codigo="codigo"
          :usuario-actual="sesion.usuario?.id ?? null"
          @actualizado="emit('actualizado')"
        />
      </q-menu>
    </q-btn>
  </q-page-sticky>
</template>
