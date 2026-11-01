<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { useQuasar } from 'quasar';
import type {
  Lote,
  LoteParada,
  RegistrarEntradaParada,
  RegistrarSalidaParada,
} from '@aleacion-prima/types';
import { useApi } from '@/services/api';

interface ErrorApi {
  message?: string;
  errors?: Record<string, string[]>;
}

const props = defineProps<{
  modelValue: boolean;
  lote: Lote;
  parada: LoteParada;
  tipo: 'entrada' | 'salida';
}>();

const emit = defineEmits<{
  'update:modelValue': [abierto: boolean];
  guardado: [lote: Lote];
}>();

const $q = useQuasar();
const fecha = new Intl.DateTimeFormat('es-MX', { dateStyle: 'medium', timeStyle: 'short' });

const piezas = ref<number | null>(null);
const mermaMaterial = ref<number | null>(null);
const retorno = ref<number | null>(null);
const hora = ref('');
const guardando = ref(false);
const errores = ref<Record<string, string[]>>({});

const anterior = computed(() =>
  props.lote.paradas.find((actual) => actual.orden === props.parada.orden - 1),
);

const esperado = computed(() =>
  props.tipo === 'entrada'
    ? (anterior.value?.piezas_entregadas ?? props.lote.cantidad_objetivo)
    : (props.parada.piezas_recibidas ?? 0),
);

const titulo = computed(() =>
  props.tipo === 'entrada'
    ? `Registrar entrada a ${props.parada.area.nombre}`
    : `Registrar salida de ${props.parada.area.nombre}`,
);

const subtitulo = computed(() => {
  const posicion = `Parada ${props.parada.orden} de ${props.lote.paradas.length}`;
  if (props.tipo === 'salida' && props.parada.entrada_ocurrido_en) {
    return `${posicion} · entró el ${fecha.format(new Date(props.parada.entrada_ocurrido_en))} · ${props.parada.piezas_recibidas ?? 0} piezas`;
  }
  return anterior.value
    ? `${posicion} · ${anterior.value.area.nombre} entregó ${esperado.value} piezas`
    : `${posicion} · ${props.lote.cantidad_objetivo} piezas objetivo`;
});

const diferencia = computed(() =>
  props.tipo === 'entrada' && anterior.value && piezas.value !== null
    ? piezas.value - esperado.value
    : 0,
);

const merma = computed(() => Math.max(esperado.value - (piezas.value ?? 0), 0));

const ahoraLocal = (): string => {
  const ahora = new Date();
  ahora.setSeconds(0, 0);
  return new Date(ahora.getTime() - ahora.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
};

const reiniciar = (): void => {
  piezas.value = esperado.value;
  mermaMaterial.value = null;
  retorno.value = null;
  hora.value = ahoraLocal();
  errores.value = {};
};

watch(
  () => props.modelValue,
  (abierto) => {
    if (abierto) reiniciar();
  },
  { immediate: true },
);

const error = (campo: string): string => errores.value[campo]?.[0] ?? '';

const avisos = computed(() => errores.value.parada ?? []);

const cerrar = (): void => emit('update:modelValue', false);

const guardar = async (): Promise<void> => {
  if (piezas.value === null) return;

  guardando.value = true;
  errores.value = {};
  const ocurrido = hora.value ? new Date(hora.value).toISOString() : undefined;
  const url = `/lotes/${props.lote.codigo}/paradas/${props.parada.id}/${props.tipo}`;

  const cuerpo: RegistrarEntradaParada | RegistrarSalidaParada =
    props.tipo === 'entrada'
      ? { piezas_recibidas: piezas.value, ...(ocurrido ? { ocurrido_en: ocurrido } : {}) }
      : {
          piezas_entregadas: piezas.value,
          ...(mermaMaterial.value !== null ? { merma_material: mermaMaterial.value } : {}),
          ...(retorno.value !== null ? { retorno_kg: retorno.value } : {}),
          ...(ocurrido ? { ocurrido_en: ocurrido } : {}),
        };

  const { data, error: fallo } = await useApi(url).post(cuerpo).json<Lote | ErrorApi>();
  guardando.value = false;

  if (fallo.value) {
    errores.value = (data.value as ErrorApi | null)?.errors ?? {};
    if (!Object.keys(errores.value).length) {
      $q.notify({ type: 'negative', message: 'No se pudo guardar la captura' });
    }
    return;
  }

  $q.notify({
    type: 'positive',
    message: props.tipo === 'entrada' ? 'Entrada registrada' : 'Salida registrada',
  });
  emit('guardado', data.value as Lote);
  cerrar();
};
</script>

<template>
  <q-dialog :model-value="modelValue" @update:model-value="emit('update:modelValue', $event)">
    <q-card class="dialogo-captura">
      <q-card-section class="row items-start no-wrap">
        <div>
          <div class="text-h6">{{ titulo }}</div>
          <div class="text-caption text-secundario">{{ subtitulo }}</div>
        </div>
        <q-space />
        <q-btn flat round dense icon="close" aria-label="Cerrar" @click="cerrar" />
      </q-card-section>

      <q-card-section class="q-pt-none">
        <q-banner
          v-for="mensaje in avisos"
          :key="mensaje"
          dense
          rounded
          class="bg-red-1 text-negative q-mb-md"
        >
          {{ mensaje }}
        </q-banner>

        <div class="row q-col-gutter-md">
          <div class="col-12 col-sm-6">
            <q-input
              v-model.number="piezas"
              type="number"
              min="0"
              :max="tipo === 'salida' ? esperado : undefined"
              :label="tipo === 'entrada' ? 'Piezas recibidas' : 'Piezas entregadas'"
              outlined
              autofocus
              :hint="tipo === 'salida' ? `Máximo ${esperado}` : undefined"
              :error="Boolean(error('piezas_recibidas') || error('piezas_entregadas'))"
              :error-message="error('piezas_recibidas') || error('piezas_entregadas')"
            />
            <div
              v-if="diferencia !== 0"
              class="text-caption text-warning text-weight-medium q-mt-xs diferencia"
            >
              Diferencia: {{ diferencia > 0 ? '+' : '' }}{{ diferencia }} pzas
            </div>
          </div>

          <div v-if="tipo === 'salida'" class="col-12 col-sm-6">
            <q-input :model-value="merma" label="Merma (piezas)" outlined readonly />
          </div>

          <div v-if="tipo === 'salida' && parada.admite_retorno" class="col-12 col-sm-6">
            <q-input
              v-model.number="retorno"
              type="number"
              min="0"
              step="0.001"
              label="Retorno al horno (kg)"
              outlined
              :error="Boolean(error('retorno_kg'))"
              :error-message="error('retorno_kg')"
            />
          </div>

          <div v-if="tipo === 'salida'" class="col-12 col-sm-6">
            <q-input
              v-model.number="mermaMaterial"
              type="number"
              min="0"
              step="0.001"
              label="Merma de material (kg)"
              hint="Opcional"
              outlined
              :error="Boolean(error('merma_material'))"
              :error-message="error('merma_material')"
            />
          </div>

          <div class="col-12">
            <q-input
              v-model="hora"
              type="datetime-local"
              label="Ocurrió el"
              stack-label
              outlined
              :error="Boolean(error('ocurrido_en'))"
              :error-message="error('ocurrido_en')"
            />
          </div>
        </div>
      </q-card-section>

      <q-card-actions align="right" class="q-px-md q-pb-md">
        <q-btn flat no-caps color="primary" label="Cancelar" @click="cerrar" />
        <q-btn
          unelevated
          no-caps
          color="primary"
          icon="check"
          :label="tipo === 'entrada' ? 'Guardar entrada' : 'Guardar salida'"
          :loading="guardando"
          :disable="piezas === null"
          @click="guardar"
        />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>

<style scoped lang="scss">
.dialogo-captura {
  width: 560px;
  max-width: 92vw;
}

.diferencia {
  animation: aparecer $duracion-rapida $curva-salida;
}
</style>
