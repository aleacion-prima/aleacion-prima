<template>
  <div class="parada column q-pa-md" :class="`parada--${estado}`">
    <div class="row items-center no-wrap q-gutter-x-sm">
      <q-badge :color="estado === 'pendiente' ? 'grey-7' : 'dark'" rounded :label="orden" />
      <span class="codigo">{{ codigo }}</span>
      <q-space />
      <q-icon v-if="estado === 'terminada'" name="check" color="positive" size="16px" />
      <q-icon v-else-if="estado === 'en_curso'" name="circle" color="accent" size="10px" />
      <q-icon
        v-else-if="estado === 'planeada' && obligatoria"
        name="lock"
        color="grey-7"
        size="16px"
      />
      <q-icon v-else-if="estado === 'planeada'" name="drag_indicator" color="grey-7" size="16px" />
    </div>
    <div class="row items-center no-wrap q-gutter-x-sm q-mt-xs">
      <q-avatar
        size="32px"
        :color="estado === 'en_curso' ? 'orange-1' : 'grey-3'"
        :text-color="estado === 'pendiente' ? 'grey-7' : 'dark'"
        :icon="iconoArea(codigo)"
        font-size="20px"
      />
      <span class="text-weight-medium" :class="{ 'text-secundario': estado === 'pendiente' }">
        {{ nombre }}
      </span>
    </div>
    <div
      v-if="tiempo"
      class="text-caption q-mt-xs"
      :class="estado === 'pendiente' ? 'text-secundario' : ''"
    >
      {{ tiempo }}
    </div>
    <div v-if="piezas" class="text-caption text-secundario">{{ piezas }}</div>
    <q-chip
      v-if="aviso"
      dense
      square
      color="warning"
      text-color="white"
      icon="warning"
      class="q-ml-none q-mt-sm"
      :label="aviso"
    />
  </div>
</template>

<script setup lang="ts">
import { iconoArea, type EstadoParada } from '@/catalogos/areas';

withDefaults(
  defineProps<{
    orden: number;
    codigo: string;
    nombre: string;
    estado: EstadoParada;
    tiempo?: string;
    piezas?: string;
    aviso?: string;
    obligatoria?: boolean;
  }>(),
  { tiempo: '', piezas: '', aviso: '', obligatoria: false },
);
</script>

<style scoped lang="scss">
.parada {
  width: 160px;
  background: white;
  border: 2px solid $acero-claro;
  border-radius: 8px;

  &--terminada {
    border-color: $positive;
  }

  &--en_curso {
    border-color: $accent;
    background: rgba($accent, 0.06);
    box-shadow: 0 0 0 4px rgba($accent, 0.25);
  }

  &--pendiente {
    border-style: dashed;
    background: $blanco-humo;
  }
}
</style>
