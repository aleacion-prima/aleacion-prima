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

<template>
  <div class="parada column q-pa-md q-gutter-y-xs" :class="`parada--${estado}`">
    <div class="row items-center no-wrap q-gutter-x-sm">
      <q-badge :color="estado === 'pendiente' ? 'grey-7' : 'dark'" rounded :label="orden" />
      <span class="codigo">{{ codigo }}</span>
      <q-space />
      <q-icon v-if="estado === 'terminada'" name="check" color="positive" size="16px" />
      <span v-else-if="estado === 'en_curso'" class="parada__pulso" />
      <q-icon
        v-else-if="estado === 'planeada' && obligatoria"
        name="lock"
        color="grey-7"
        size="16px"
      />
      <q-icon v-else-if="estado === 'planeada'" name="drag_indicator" color="grey-7" size="16px" />
    </div>
    <div class="row items-center no-wrap q-gutter-x-sm">
      <q-avatar
        size="32px"
        :color="estado === 'en_curso' ? 'orange-1' : 'grey-3'"
        :text-color="estado === 'pendiente' ? 'grey-7' : 'dark'"
        :icon="iconoArea(codigo)"
        font-size="20px"
        class="parada__icono"
        :class="`parada__icono--${codigo}`"
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
    <div
      v-if="aviso"
      class="row items-start no-wrap bg-warning text-white text-caption text-weight-medium rounded-borders q-px-sm q-py-xs q-mt-sm"
    >
      <q-icon name="warning" size="14px" class="q-mr-xs q-mt-xs" />
      <span>{{ aviso }}</span>
    </div>
  </div>
</template>

<style scoped lang="scss">
.parada {
  width: 168px;
  min-height: 128px;
  background: white;
  border: 2px solid $acero-claro;
  border-radius: 8px;
  transition:
    transform $duracion-rapida $curva-salida,
    box-shadow $duracion-rapida $curva-salida;

  &:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px -12px rgba($dark, 0.35);
  }

  &__pulso {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: $accent;
    animation: pulso 1.4s ease-out infinite;
  }

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

.parada--en_curso .parada__icono :deep(.q-icon) {
  transform-origin: 50% 85%;
}

.parada--en_curso .parada__icono--MOL :deep(.q-icon) {
  animation: area-moldeo 2.4s ease-in-out infinite;
}

.parada--en_curso .parada__icono--FUS :deep(.q-icon) {
  animation: area-fusion 0.9s ease-in-out infinite;
}

.parada--en_curso .parada__icono--COL :deep(.q-icon) {
  animation: area-colada 1.8s ease-in infinite;
}

.parada--en_curso .parada__icono--DES :deep(.q-icon) {
  animation: area-desmolde 1.6s ease-in-out infinite;
}

.parada--en_curso .parada__icono--GRA :deep(.q-icon) {
  animation: area-granallado 2.4s linear infinite;
}

.parada--en_curso .parada__icono--CAL :deep(.q-icon) {
  animation: area-calidad 2.2s ease-in-out infinite;
}

.parada--en_curso .parada__icono--EMP :deep(.q-icon) {
  animation: area-empaque 2s ease-in-out infinite;
}
</style>
