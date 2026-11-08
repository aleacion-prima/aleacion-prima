<script setup lang="ts">
import { computed, ref } from 'vue';
import type { Rol } from '@aleacion-prima/types';
import { ETIQUETAS_ROL } from '@/catalogos/roles';
import LogoMarca from './LogoMarca.vue';

const props = withDefaults(defineProps<{ nombre: string; rol: Rol; pendientes?: number }>(), {
  pendientes: 0,
});

const emit = defineEmits<{ 'alternar-menu': []; salir: [] }>();

const busqueda = ref('');
const rolEtiqueta = computed(() => ETIQUETAS_ROL[props.rol]);
</script>

<template>
  <q-header class="bg-secondary">
    <q-toolbar class="q-px-md" style="min-height: 64px">
      <q-btn flat dense round icon="menu" aria-label="Menú" @click="emit('alternar-menu')" />
      <q-toolbar-title shrink class="row items-center no-wrap q-gutter-x-sm">
        <LogoMarca />
        <span class="text-h6 text-uppercase marca">Aleación Prima</span>
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
      <q-btn flat round icon="notifications" aria-label="Notificaciones">
        <q-badge v-if="pendientes" color="accent" text-color="dark" floating rounded>
          {{ pendientes }}
        </q-badge>
        <q-menu anchor="bottom right" self="top right" :offset="[0, 8]">
          <div class="vistazo">
            <div class="text-subtitle1 text-weight-medium q-px-md q-pt-md">Notificaciones</div>
            <div class="column items-center q-pa-lg text-secundario">
              <q-icon name="notifications_none" size="32px" />
              <div class="q-mt-sm">Sin notificaciones</div>
            </div>
          </div>
        </q-menu>
      </q-btn>
      <div class="column items-end q-mx-sm">
        <span class="text-weight-medium">{{ nombre }}</span>
        <span class="text-caption">{{ rolEtiqueta }}</span>
      </div>
      <q-btn flat round icon="account_circle" aria-label="Cuenta">
        <q-menu>
          <q-list>
            <q-item v-close-popup clickable to="/mi-cuenta">
              <q-item-section avatar><q-icon name="manage_accounts" /></q-item-section>
              <q-item-section>Mi cuenta</q-item-section>
            </q-item>
            <q-separator />
            <q-item v-close-popup clickable @click="emit('salir')">
              <q-item-section avatar><q-icon name="logout" /></q-item-section>
              <q-item-section>Cerrar sesión</q-item-section>
            </q-item>
          </q-list>
        </q-menu>
      </q-btn>
    </q-toolbar>
  </q-header>
</template>

<style scoped>
.marca {
  letter-spacing: 0.08em;
}

.vistazo {
  width: 320px;
}
</style>
