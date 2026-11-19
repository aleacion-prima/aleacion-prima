<script setup lang="ts">
import { computed, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useSesionStore } from '@/stores/sesion';

const route = useRoute();
const router = useRouter();
const sesion = useSesionStore();
const saliendo = ref(false);

const codigo = computed(() =>
  typeof route.params.codigo === 'string' ? route.params.codigo : null,
);

const salir = async (): Promise<void> => {
  saliendo.value = true;
  try {
    await sesion.cerrar();
    await router.replace('/login');
  } finally {
    saliendo.value = false;
  }
};
</script>

<template>
  <q-layout view="hHh lpR fFf">
    <q-header class="bg-secondary">
      <q-toolbar class="barra-movil">
        <q-btn
          v-if="codigo"
          flat
          round
          dense
          icon="arrow_back"
          aria-label="Volver"
          @click="router.replace('/')"
        />
        <q-toolbar-title class="barra-movil__titulo" :class="{ codigo }">
          {{ codigo ?? 'Aleación Prima' }}
        </q-toolbar-title>
        <q-btn
          v-if="!codigo"
          flat
          round
          dense
          icon="logout"
          aria-label="Cerrar sesión"
          :loading="saliendo"
          @click="salir"
        />
      </q-toolbar>
    </q-header>

    <q-page-container>
      <router-view />
    </q-page-container>
  </q-layout>
</template>

<style scoped lang="scss">
.barra-movil {
  padding: 0 8px 0 16px;
  gap: 8px;
}

.barra-movil__titulo {
  padding: 0;
  font-family: $fuente-titulos;
  font-size: 20px;
  font-weight: 600;
  line-height: 24px;
}

.barra-movil__titulo.codigo {
  font-family: $fuente-codigo;
  font-size: 18px;
  font-weight: 500;
}
</style>
