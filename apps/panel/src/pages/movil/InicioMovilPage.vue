<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useSesionStore } from '@/stores/sesion';

const router = useRouter();
const sesion = useSesionStore();
const saliendo = ref(false);

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
  <q-page class="column flex-center q-pa-lg q-gutter-md">
    <div class="text-h6">{{ sesion.usuario?.name }}</div>
    <q-btn
      flat
      no-caps
      color="primary"
      icon="logout"
      label="Cerrar sesión"
      :loading="saliendo"
      @click="salir"
    />
  </q-page>
</template>
