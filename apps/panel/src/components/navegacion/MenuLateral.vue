<script setup lang="ts">
import { computed } from 'vue';
import type { Rol } from '@aleacion-prima/types';
import { MENU_POR_ROL } from '@/catalogos/menu';

const props = withDefaults(defineProps<{ rol?: Rol }>(), { rol: 'administrador' });

const secciones = computed(() => MENU_POR_ROL[props.rol]);
</script>

<template>
  <q-list padding>
    <template v-for="(seccion, i) in secciones" :key="i">
      <q-item-label
        v-if="seccion.titulo"
        header
        class="text-uppercase text-caption text-secundario q-pt-md q-pb-xs q-mini-drawer-hide"
      >
        {{ seccion.titulo }}
      </q-item-label>
      <q-item
        v-for="elemento in seccion.elementos"
        :key="elemento.ruta"
        :to="elemento.ruta"
        exact
        clickable
        dense
        class="rounded-borders q-mx-sm"
        active-class="bg-grey-2 text-primary text-weight-medium"
      >
        <q-item-section avatar>
          <q-icon :name="elemento.icono" />
        </q-item-section>
        <q-item-section>{{ elemento.etiqueta }}</q-item-section>
        <q-tooltip anchor="center right" self="center left" class="q-mini-drawer-only">
          {{ elemento.etiqueta }}
        </q-tooltip>
      </q-item>
    </template>
  </q-list>
</template>
