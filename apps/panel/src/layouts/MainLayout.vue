<template>
  <q-layout view="lHh Lpr lFf">
    <q-header class="bg-secondary">
      <q-toolbar class="q-px-md" style="min-height: 64px">
        <q-btn
          flat
          dense
          round
          icon="menu"
          aria-label="Menú"
          class="lt-md"
          @click="menuAbierto = !menuAbierto"
        />
        <q-toolbar-title shrink class="text-h6 text-uppercase" style="letter-spacing: 0.08em">
          Aleación Prima
        </q-toolbar-title>
        <q-space />
        <q-input
          v-model="busqueda"
          dense
          dark
          standout="bg-grey-9"
          placeholder="Buscar lote, producto o insumo…"
          class="gt-sm"
          style="width: 560px"
        >
          <template #prepend><q-icon name="search" /></template>
        </q-input>
        <q-space />
        <q-btn flat round icon="notifications" aria-label="Notificaciones" to="/notificaciones">
          <q-badge v-if="pendientes" color="accent" text-color="dark" floating rounded>{{
            pendientes
          }}</q-badge>
        </q-btn>
        <div class="column items-end q-mx-sm">
          <span class="text-weight-medium">{{ sesion.usuario?.name }}</span>
          <span class="text-caption">{{ rolEtiqueta }}</span>
        </div>
        <q-btn flat round icon="account_circle" aria-label="Cuenta">
          <q-menu>
            <q-list>
              <q-item v-close-popup clickable @click="salir">
                <q-item-section avatar><q-icon name="logout" /></q-item-section>
                <q-item-section>Cerrar sesión</q-item-section>
              </q-item>
            </q-list>
          </q-menu>
        </q-btn>
      </q-toolbar>
    </q-header>

    <q-drawer v-model="menuAbierto" show-if-above bordered :width="240">
      <MenuLateral :rol="rol" />
    </q-drawer>

    <q-page-container>
      <router-view />
    </q-page-container>
  </q-layout>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { useRouter } from 'vue-router';
import type { Rol } from '@aleacion-prima/types';
import MenuLateral from '@/components/navegacion/MenuLateral.vue';
import { useSesionStore } from '@/stores/sesion';

const ETIQUETAS_ROL: Record<Rol, string> = {
  administrador: 'Administrador',
  ventas: 'Ventas',
  encargado: 'Encargado',
  cliente: 'Cliente',
};

const router = useRouter();
const sesion = useSesionStore();

const menuAbierto = ref(false);
const busqueda = ref('');
const pendientes = ref(0);
const rol = computed<Rol>(() => 'administrador');
const rolEtiqueta = computed(() => ETIQUETAS_ROL[rol.value]);

const salir = async (): Promise<void> => {
  await sesion.cerrar();
  await router.push('/login');
};
</script>
