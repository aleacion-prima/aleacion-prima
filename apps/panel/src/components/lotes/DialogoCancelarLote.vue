<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { useQuasar } from 'quasar';
import type { CancelarLote, Lote } from '@aleacion-prima/types';
import { useApi } from '@/services/api';
import { formatearCantidad } from '@/composables/formato';

interface ErrorApi {
  message?: string;
  errors?: Record<string, string[]>;
}

const props = defineProps<{ modelValue: boolean; lote: Lote }>();
const emit = defineEmits<{ 'update:modelValue': [abierto: boolean]; guardado: [lote: Lote] }>();

const $q = useQuasar();
const motivo = ref('');
const cantidades = ref<Record<number, number | null>>({});
const guardando = ref(false);
const errores = ref<Record<string, string[]>>({});
const enviados = ref<number[]>([]);

const disponibles = computed(() =>
  (props.lote.insumos ?? [])
    .map((asignado) => ({
      ...asignado,
      disponible: Number(asignado.cantidad) - Number(asignado.cantidad_devuelta),
    }))
    .filter((asignado) => asignado.disponible > 0),
);

const devolverTodo = (): void => {
  cantidades.value = Object.fromEntries(
    disponibles.value.map((asignado) => [asignado.insumo.id, asignado.disponible]),
  );
};

watch(
  () => props.modelValue,
  (abierto) => {
    if (!abierto) return;
    motivo.value = '';
    cantidades.value = {};
    errores.value = {};
  },
);

const error = (campo: string): string => errores.value[campo]?.[0] ?? '';

const errorDevolucion = (insumoId: number): string => {
  const posicion = enviados.value.indexOf(insumoId);
  return posicion === -1 ? '' : error(`devoluciones.${posicion}.cantidad`);
};

const cerrar = (): void => emit('update:modelValue', false);

const cancelar = async (): Promise<void> => {
  guardando.value = true;
  errores.value = {};
  const devoluciones = disponibles.value
    .map((asignado) => ({
      insumo_id: asignado.insumo.id,
      cantidad: Number(cantidades.value[asignado.insumo.id] ?? 0),
    }))
    .filter((devolucion) => devolucion.cantidad > 0);
  enviados.value = devoluciones.map((devolucion) => devolucion.insumo_id);
  const cuerpo: CancelarLote = {
    motivo: motivo.value,
    ...(devoluciones.length ? { devoluciones } : {}),
  };

  const { data, error: fallo } = await useApi(`/lotes/${props.lote.codigo}/cancelar`)
    .post(cuerpo)
    .json<Lote | ErrorApi>();
  guardando.value = false;

  if (fallo.value) {
    errores.value = (data.value as ErrorApi | null)?.errors ?? {};
    if (!Object.keys(errores.value).length) {
      $q.notify({ type: 'negative', message: 'No se pudo cancelar el lote' });
    }
    return;
  }

  $q.notify({ type: 'positive', message: `Lote ${props.lote.codigo} cancelado` });
  emit('guardado', data.value as Lote);
  cerrar();
};
</script>

<template>
  <q-dialog :model-value="modelValue" @update:model-value="emit('update:modelValue', $event)">
    <q-card class="dialogo-cancelar">
      <q-card-section class="row items-start no-wrap">
        <div>
          <div class="text-h6">Cancelar lote</div>
          <div class="text-caption text-secundario">
            <span class="codigo">{{ lote.codigo }}</span> · {{ lote.producto.nombre }}
          </div>
        </div>
        <q-space />
        <q-btn flat round dense icon="close" aria-label="Cerrar" @click="cerrar" />
      </q-card-section>

      <q-card-section class="q-pt-none">
        <q-banner
          v-for="mensaje in [...(errores.lote ?? []), ...(errores.devoluciones ?? [])]"
          :key="mensaje"
          dense
          rounded
          class="bg-red-1 text-negative q-mb-md"
        >
          {{ mensaje }}
        </q-banner>

        <q-input
          v-model="motivo"
          type="textarea"
          autogrow
          label="Motivo"
          outlined
          autofocus
          :error="Boolean(error('motivo'))"
          :error-message="error('motivo')"
        />

        <template v-if="lote.admite_devolucion && disponibles.length">
          <div class="row items-center justify-between q-mt-md q-mb-sm">
            <div class="text-subtitle2">Devolver al inventario</div>
            <q-btn flat dense no-caps color="primary" label="Devolver todo" @click="devolverTodo" />
          </div>
          <div
            v-for="asignado in disponibles"
            :key="asignado.id"
            class="row items-center q-col-gutter-sm q-mb-xs"
          >
            <div class="col">
              <span class="codigo">{{ asignado.insumo.codigo }}</span> ·
              {{ asignado.insumo.nombre }}
              <div class="text-caption text-secundario">
                Asignado {{ formatearCantidad(asignado.disponible) }} {{ asignado.insumo.unidad }}
              </div>
            </div>
            <div class="col-4">
              <q-input
                v-model.number="cantidades[asignado.insumo.id]"
                type="number"
                min="0"
                :max="asignado.disponible"
                step="0.001"
                dense
                outlined
                :suffix="asignado.insumo.unidad"
                :error="Boolean(errorDevolucion(asignado.insumo.id))"
                :error-message="errorDevolucion(asignado.insumo.id)"
              />
            </div>
          </div>
        </template>
      </q-card-section>

      <q-card-actions align="right" class="q-px-md q-pb-md">
        <q-btn flat no-caps color="primary" label="Volver" @click="cerrar" />
        <q-btn
          unelevated
          no-caps
          color="negative"
          icon="block"
          label="Cancelar lote"
          :loading="guardando"
          :disable="!motivo.trim()"
          @click="cancelar"
        />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>

<style scoped>
.dialogo-cancelar {
  width: 560px;
  max-width: 92vw;
}
</style>
