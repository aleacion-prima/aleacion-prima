<script setup lang="ts">
import { computed, ref } from 'vue';
import { useQuasar } from 'quasar';
import { useRouter } from 'vue-router';
import type { Rol } from '@aleacion-prima/types';
import BarraSuperior from '@/components/navegacion/BarraSuperior.vue';
import MenuLateral from '@/components/navegacion/MenuLateral.vue';
import { useSesionStore } from '@/stores/sesion';

const $q = useQuasar();
const router = useRouter();
const sesion = useSesionStore();

const menuAbierto = ref(false);
const menuMini = ref(false);
const rol = computed<Rol>(() => sesion.usuario?.rol ?? 'encargado');

const alternarMenu = (): void => {
  if ($q.screen.lt.md) menuAbierto.value = !menuAbierto.value;
  else menuMini.value = !menuMini.value;
};

const salir = async (): Promise<void> => {
  await sesion.cerrar();
  await router.push('/login');
};
</script>

<template>
  <q-layout view="lHh Lpr lFf">
    <BarraSuperior
      :nombre="sesion.usuario?.name ?? ''"
      :rol="rol"
      @alternar-menu="alternarMenu"
      @salir="salir"
    />

    <q-drawer
      v-model="menuAbierto"
      :mini="menuMini"
      show-if-above
      bordered
      :width="232"
      :mini-width="64"
    >
      <MenuLateral :rol="rol" />
    </q-drawer>

    <q-page-container>
      <router-view #default="{ Component }">
        <transition name="q-transition--fade" mode="out-in">
          <component :is="Component" />
        </transition>
      </router-view>
    </q-page-container>
  </q-layout>
</template>
