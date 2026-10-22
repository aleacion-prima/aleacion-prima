<script setup lang="ts">
import { computed } from 'vue';

export type TipoAviso = 'exito' | 'error' | 'advertencia' | 'informacion';

const ESTILOS: Record<TipoAviso, { icono: string; color: string }> = {
  exito: { icono: 'check_circle', color: 'positive' },
  error: { icono: 'cancel', color: 'negative' },
  advertencia: { icono: 'warning', color: 'warning' },
  informacion: { icono: 'info', color: 'info' },
};

const props = withDefaults(
  defineProps<{ tipo?: TipoAviso; mensaje?: string; cerrable?: boolean }>(),
  { tipo: 'informacion', mensaje: '', cerrable: true },
);

const emit = defineEmits<{ cerrar: [] }>();

const icono = computed(() => ESTILOS[props.tipo].icono);
const color = computed(() => ESTILOS[props.tipo].color);
</script>

<template>
  <div class="aviso row items-center no-wrap bg-dark text-white rounded-borders q-px-md q-py-sm">
    <q-icon :name="icono" :color="color" size="22px" class="q-mr-md" />
    <div class="col">
      <slot>{{ mensaje }}</slot>
    </div>
    <q-btn
      v-if="cerrable"
      flat
      round
      dense
      icon="close"
      aria-label="Cerrar aviso"
      class="q-ml-sm"
      @click="emit('cerrar')"
    />
  </div>
</template>
