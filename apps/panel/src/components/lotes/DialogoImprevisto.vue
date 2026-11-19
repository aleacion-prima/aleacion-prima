<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue';
import { useQuasar } from 'quasar';
import type { Imprevisto, Lote, LoteParada, TipoImprevisto } from '@aleacion-prima/types';
import { opcionesTipoImprevisto } from '@/catalogos/imprevistos';
import { useApi } from '@/services/api';

interface FormularioImprevisto {
  tipo: TipoImprevisto | null;
  parada_id: number | null;
  descripcion: string;
  maquina: string;
  piezas_afectadas: number | null;
  duracion_minutos: number | null;
  ocurrido_en: string;
  resuelto: boolean;
}

interface ErrorApi {
  message?: string;
  errors?: Record<string, string[]>;
}

const props = defineProps<{
  lote: Lote;
  paradas: LoteParada[];
  paradaInicial?: number | undefined;
}>();

const abierto = defineModel<boolean>({ required: true });
const emit = defineEmits<{ guardado: [imprevisto: Imprevisto] }>();

const $q = useQuasar();
const guardando = ref(false);
const errores = ref<Record<string, string[]>>({});
const formulario = reactive<FormularioImprevisto>({
  tipo: null,
  parada_id: null,
  descripcion: '',
  maquina: '',
  piezas_afectadas: null,
  duracion_minutos: null,
  ocurrido_en: '',
  resuelto: false,
});

const ahoraLocal = (): string => {
  const ahora = new Date();
  ahora.setMinutes(ahora.getMinutes() - ahora.getTimezoneOffset());
  return ahora.toISOString().slice(0, 16);
};

const opcionesArea = computed(() =>
  props.paradas.map((parada) => ({
    label:
      props.paradas.filter((otra) => otra.area.id === parada.area.id).length > 1
        ? `${parada.area.nombre} · parada ${parada.orden}`
        : parada.area.nombre,
    value: parada.id,
  })),
);

const campoError = (campo: string): string => errores.value[campo]?.[0] ?? '';

const reiniciar = (): void => {
  errores.value = {};
  formulario.tipo = null;
  formulario.parada_id = props.paradaInicial ?? props.paradas[0]?.id ?? null;
  formulario.descripcion = '';
  formulario.maquina = '';
  formulario.piezas_afectadas = null;
  formulario.duracion_minutos = null;
  formulario.ocurrido_en = ahoraLocal();
  formulario.resuelto = false;
};

watch(abierto, (valor) => {
  if (valor) reiniciar();
});

const guardar = async (): Promise<void> => {
  if (formulario.parada_id === null) {
    errores.value = { parada: ['Elige el área donde ocurrió.'] };
    return;
  }

  guardando.value = true;
  errores.value = {};
  const { error, data } = await useApi(
    `/lotes/${encodeURIComponent(props.lote.codigo)}/paradas/${formulario.parada_id}/imprevistos`,
  )
    .post({
      tipo: formulario.tipo,
      descripcion: formulario.descripcion,
      maquina: formulario.maquina.trim() === '' ? null : formulario.maquina,
      piezas_afectadas: formulario.piezas_afectadas,
      duracion_minutos: formulario.duracion_minutos,
      ocurrido_en: formulario.ocurrido_en === '' ? null : formulario.ocurrido_en,
      resuelto: formulario.resuelto,
    })
    .json<Imprevisto>();
  guardando.value = false;

  if (error.value) {
    const detalle = data.value as ErrorApi | null;
    errores.value = detalle?.errors ?? {};
    $q.notify({
      type: 'negative',
      message:
        Object.values(errores.value)[0]?.[0] ??
        detalle?.message ??
        'No se pudo guardar el imprevisto',
    });
    return;
  }

  abierto.value = false;
  if (data.value) emit('guardado', data.value);
  $q.notify({ type: 'positive', message: 'Imprevisto registrado' });
};
</script>

<template>
  <q-dialog v-model="abierto" persistent>
    <q-card style="width: 640px; max-width: 95vw">
      <q-card-section class="row items-start no-wrap">
        <div class="col">
          <div class="text-h6">Registrar imprevisto</div>
          <div class="text-caption text-secundario">
            <span class="codigo">{{ lote.codigo }}</span> · {{ lote.producto.nombre }}
          </div>
        </div>
        <q-btn v-close-popup flat round dense icon="close" aria-label="Cerrar" />
      </q-card-section>
      <q-separator />

      <q-card-section class="scroll" style="max-height: 68vh">
        <div class="row q-col-gutter-md">
          <q-select
            v-model="formulario.tipo"
            class="col-12 col-sm-6"
            :options="opcionesTipoImprevisto"
            label="Tipo"
            emit-value
            map-options
            outlined
            :error="campoError('tipo') !== ''"
            :error-message="campoError('tipo')"
          />
          <q-select
            v-model="formulario.parada_id"
            class="col-12 col-sm-6"
            :options="opcionesArea"
            label="Área"
            emit-value
            map-options
            outlined
            :error="campoError('parada') !== ''"
            :error-message="campoError('parada')"
          />
          <q-input
            v-model="formulario.descripcion"
            class="col-12"
            label="Descripción"
            type="textarea"
            autogrow
            outlined
            :error="campoError('descripcion') !== ''"
            :error-message="campoError('descripcion')"
          />
          <q-input
            v-model="formulario.maquina"
            class="col-12"
            label="Máquina"
            hint="Opcional"
            outlined
            :error="campoError('maquina') !== ''"
            :error-message="campoError('maquina')"
          />
          <q-input
            v-model.number="formulario.piezas_afectadas"
            class="col-12 col-sm-6"
            label="Piezas afectadas"
            hint="Opcional"
            type="number"
            min="0"
            outlined
            :error="campoError('piezas_afectadas') !== ''"
            :error-message="campoError('piezas_afectadas')"
          />
          <q-input
            v-model.number="formulario.duracion_minutos"
            class="col-12 col-sm-6"
            label="Duración (min)"
            hint="Opcional"
            type="number"
            min="0"
            outlined
            :error="campoError('duracion_minutos') !== ''"
            :error-message="campoError('duracion_minutos')"
          />
          <q-input
            v-model="formulario.ocurrido_en"
            class="col-12 col-sm-8"
            label="Ocurrió el"
            type="datetime-local"
            stack-label
            outlined
            :error="campoError('ocurrido_en') !== ''"
            :error-message="campoError('ocurrido_en')"
          />
          <div class="col-12 col-sm-4 flex items-center">
            <q-checkbox v-model="formulario.resuelto" label="Ya está resuelto" color="primary" />
          </div>
        </div>
      </q-card-section>

      <q-separator />
      <q-card-actions align="right">
        <q-btn v-close-popup flat no-caps color="primary" label="Cancelar" :disable="guardando" />
        <q-btn
          unelevated
          no-caps
          color="primary"
          icon="warning"
          label="Guardar imprevisto"
          :loading="guardando"
          @click="guardar"
        />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>
