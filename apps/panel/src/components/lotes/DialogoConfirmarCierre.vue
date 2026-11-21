<script setup lang="ts">
import { computed, ref } from 'vue';
import { useQuasar } from 'quasar';
import type { Lote } from '@aleacion-prima/types';
import { useApi } from '@/services/api';
import { conPlural, piezas } from '@/composables/formato';

interface ErrorApi {
  message?: string;
  errors?: Record<string, string[]>;
}

const props = defineProps<{ modelValue: boolean; lote: Lote }>();
const emit = defineEmits<{ 'update:modelValue': [abierto: boolean]; guardado: [lote: Lote] }>();

const $q = useQuasar();
const guardando = ref(false);
const error = ref('');

const terminadas = computed(() => props.lote.paradas.at(-1)?.piezas_entregadas ?? 0);
const merma = computed(() =>
  props.lote.paradas.reduce((total, parada) => total + (parada.merma_piezas ?? 0), 0),
);
const faltantes = computed(() =>
  Math.max(props.lote.cantidad_objetivo - terminadas.value - merma.value, 0),
);
const columna = computed(() => (faltantes.value > 0 ? 'col-3' : 'col-4'));
const rendimiento = computed(() =>
  props.lote.cantidad_objetivo
    ? Math.round((terminadas.value / props.lote.cantidad_objetivo) * 100)
    : 0,
);

const cerrar = (): void => emit('update:modelValue', false);

const confirmar = async (): Promise<void> => {
  guardando.value = true;
  error.value = '';
  const { data, error: fallo } = await useApi(`/lotes/${props.lote.codigo}/confirmar`)
    .post()
    .json<Lote | ErrorApi>();
  guardando.value = false;

  if (fallo.value) {
    const cuerpo = data.value as ErrorApi | null;
    error.value = cuerpo?.errors?.lote?.[0] ?? cuerpo?.message ?? 'No se pudo confirmar el cierre';
    return;
  }

  $q.notify({ type: 'positive', message: `Lote ${props.lote.codigo} terminado` });
  emit('guardado', data.value as Lote);
  cerrar();
};
</script>

<template>
  <q-dialog :model-value="modelValue" @update:model-value="emit('update:modelValue', $event)">
    <q-card class="dialogo-cierre">
      <q-card-section class="row items-start no-wrap">
        <div>
          <div class="text-h6">Confirmar cierre</div>
          <div class="text-caption text-secundario">
            <span class="codigo">{{ lote.codigo }}</span> · {{ lote.producto.nombre }}
          </div>
        </div>
        <q-space />
        <q-btn flat round dense icon="close" aria-label="Cerrar" @click="cerrar" />
      </q-card-section>

      <q-card-section class="q-pt-none">
        <q-banner v-if="error" dense rounded class="bg-red-1 text-negative q-mb-md">{{
          error
        }}</q-banner>

        <div class="row q-col-gutter-md text-center">
          <div :class="columna">
            <div class="text-overline text-secundario">Terminadas</div>
            <div class="text-h5 codigo">{{ terminadas }}</div>
          </div>
          <div :class="columna">
            <div class="text-overline text-secundario">Merma</div>
            <div class="text-h5 codigo" :class="{ 'text-negative': merma > 0 }">{{ merma }}</div>
          </div>
          <div v-if="faltantes > 0" :class="columna">
            <div class="text-overline text-secundario">Faltantes</div>
            <div class="text-h5 codigo text-warning">{{ faltantes }}</div>
          </div>
          <div :class="columna">
            <div class="text-overline text-secundario">Rendimiento</div>
            <div class="text-h5 codigo">{{ rendimiento }} %</div>
          </div>
        </div>

        <div class="text-body2 q-mt-md">
          {{ piezas(terminadas) }} {{ conPlural(terminadas, 'entra', 'entran') }} al inventario de
          producto terminado.
        </div>
      </q-card-section>

      <q-card-actions align="right" class="q-px-md q-pb-md">
        <q-btn flat no-caps color="primary" label="Cancelar" @click="cerrar" />
        <q-btn
          unelevated
          no-caps
          color="primary"
          icon="task_alt"
          label="Confirmar cierre"
          :loading="guardando"
          @click="confirmar"
        />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>

<style scoped>
.dialogo-cierre {
  width: 480px;
  max-width: 92vw;
}
</style>
