<script setup lang="ts">
import { computed, ref } from 'vue';
import type { Rol } from '@aleacion-prima/types';
import { ETIQUETAS_ROL } from '@/catalogos/roles';

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
        <q-badge v-if="pendientes" color="accent" text-color="dark" floating rounded>
          {{ pendientes }}
        </q-badge>
      </q-btn>
      <div class="column items-end q-mx-sm">
        <span class="text-weight-medium">{{ nombre }}</span>
        <span class="text-caption">{{ rolEtiqueta }}</span>
      </div>
      <q-btn flat round icon="account_circle" aria-label="Cuenta">
        <q-menu>
          <q-list>
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
