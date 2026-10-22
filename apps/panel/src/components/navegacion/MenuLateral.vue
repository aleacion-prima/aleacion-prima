<template>
  <q-list padding>
    <template v-for="(seccion, i) in secciones" :key="i">
      <q-item-label
        v-if="seccion.titulo"
        header
        class="text-uppercase text-caption text-secundario"
      >
        {{ seccion.titulo }}
      </q-item-label>
      <q-item
        v-for="elemento in seccion.elementos"
        :key="elemento.ruta"
        :to="elemento.ruta"
        exact
        clickable
        active-class="bg-grey-2 text-primary text-weight-medium"
        :inset-level="elemento.icono ? 0 : 1"
      >
        <q-item-section v-if="elemento.icono" avatar>
          <q-icon :name="elemento.icono" />
        </q-item-section>
        <q-item-section>{{ elemento.etiqueta }}</q-item-section>
      </q-item>
    </template>
  </q-list>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import type { Rol } from '@aleacion-prima/types';
import { MENU_POR_ROL } from '@/catalogos/menu';

const props = withDefaults(defineProps<{ rol?: Rol }>(), { rol: 'administrador' });

const secciones = computed(() => MENU_POR_ROL[props.rol]);
</script>
