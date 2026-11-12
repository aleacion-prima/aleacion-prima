<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { useQuasar } from 'quasar';
import type {
  AjustarLote,
  CampoAjusteLote,
  Lote,
  RespuestaAjusteLote,
} from '@aleacion-prima/types';
import { useApi } from '@/services/api';
import { formatearCantidad } from '@/composables/formato';
import {
  CAMPOS_INSUMO,
  CAMPOS_PARADA,
  etiquetaCampo,
  type CampoAjustable,
  type ObjetivoAjuste,
} from '@/catalogos/ajustes';

interface ErrorApi {
  message?: string;
  errors?: Record<string, string[]>;
}

const props = defineProps<{ modelValue: boolean; lote: Lote; objetivo: ObjetivoAjuste }>();
const emit = defineEmits<{ 'update:modelValue': [abierto: boolean]; guardado: [lote: Lote] }>();

const $q = useQuasar();

const campo = ref<CampoAjusteLote>(
  props.objetivo.tipo === 'insumo' ? 'cantidad' : 'piezas_recibidas',
);
const valor = ref('');
const motivo = ref('');
const guardando = ref(false);
const errores = ref<Record<string, string[]>>({});

const campos = computed<CampoAjustable[]>(() =>
  props.objetivo.tipo === 'insumo' ? CAMPOS_INSUMO : CAMPOS_PARADA,
);

const definicion = computed<CampoAjustable | null>(
  () => campos.value.find((opcion) => opcion.campo === campo.value) ?? null,
);

const titulo = computed(() =>
  props.objetivo.tipo === 'insumo'
    ? `${props.objetivo.insumo.insumo.codigo} · ${props.objetivo.insumo.insumo.nombre}`
    : `Parada ${props.objetivo.parada.orden} · ${props.objetivo.parada.area.nombre}`,
);

const dosDigitos = (numero: number): string => String(numero).padStart(2, '0');

const paraInputFecha = (iso: string): string => {
  const fecha = new Date(iso);
  if (Number.isNaN(fecha.getTime())) return '';
  return `${fecha.getFullYear()}-${dosDigitos(fecha.getMonth() + 1)}-${dosDigitos(
    fecha.getDate(),
  )}T${dosDigitos(fecha.getHours())}:${dosDigitos(fecha.getMinutes())}`;
};

const actualDe = (dato: CampoAjusteLote): string | number | null => {
  if (props.objetivo.tipo === 'insumo') {
    if (dato === 'cantidad') return props.objetivo.insumo.cantidad;
    if (dato === 'cantidad_devuelta') return props.objetivo.insumo.cantidad_devuelta;
    return null;
  }

  const { parada } = props.objetivo;
  if (dato === 'piezas_recibidas') return parada.piezas_recibidas;
  if (dato === 'piezas_entregadas') return parada.piezas_entregadas;
  if (dato === 'merma_material') return parada.merma_material;
  if (dato === 'entrada_ocurrido_en') return parada.entrada_ocurrido_en;
  return parada.salida_ocurrido_en;
};

const valorDe = (dato: CampoAjusteLote): string => {
  const tipo = campos.value.find((opcion) => opcion.campo === dato)?.tipo;
  const actual = actualDe(dato);
  if (actual === null) return '';
  return tipo === 'fecha' ? paraInputFecha(String(actual)) : String(actual);
};

const actualFormateado = computed(() => {
  const objetivo = props.objetivo;
  const actual = actualDe(campo.value);
  if (actual === null) return 'Sin dato';
  if (definicion.value?.tipo === 'fecha') {
    return new Intl.DateTimeFormat('es-MX', { dateStyle: 'short', timeStyle: 'short' }).format(
      new Date(String(actual)),
    );
  }
  if (definicion.value?.tipo === 'decimal' && objetivo.tipo === 'insumo') {
    return `${formatearCantidad(actual)} ${objetivo.insumo.insumo.unidad}`;
  }
  return formatearCantidad(actual, definicion.value?.tipo === 'entero' ? 0 : 3);
});

const error = (nombre: string): string => errores.value[nombre]?.[0] ?? '';

const preparar = (): void => {
  campo.value = props.objetivo.tipo === 'insumo' ? 'cantidad' : 'piezas_recibidas';
  valor.value = valorDe(campo.value);
  motivo.value = '';
  errores.value = {};
};

watch(
  () => props.modelValue,
  (abierto) => {
    if (abierto) preparar();
  },
);

watch(
  () => props.objetivo,
  () => {
    if (props.modelValue) preparar();
  },
);

const cambiarCampo = (nuevo: CampoAjusteLote): void => {
  campo.value = nuevo;
  valor.value = valorDe(nuevo);
  errores.value = {};
};

const cerrar = (): void => emit('update:modelValue', false);

const guardar = async (): Promise<void> => {
  if (!definicion.value) return;

  guardando.value = true;
  errores.value = {};

  const cuerpo: AjustarLote = {
    ...(props.objetivo.tipo === 'parada'
      ? { lote_area_id: props.objetivo.parada.id }
      : { lote_insumo_id: props.objetivo.insumo.id }),
    campo: campo.value,
    valor:
      definicion.value.tipo === 'fecha' ? new Date(valor.value).toISOString() : Number(valor.value),
    motivo: motivo.value.trim(),
  };

  const { data, error: fallo } = await useApi(`/lotes/${props.lote.codigo}/ajustes`)
    .post(cuerpo)
    .json<RespuestaAjusteLote | ErrorApi>();
  guardando.value = false;

  if (fallo.value) {
    errores.value = (data.value as ErrorApi | null)?.errors ?? {};
    if (!Object.keys(errores.value).length) {
      $q.notify({ type: 'negative', message: 'No se pudo registrar el ajuste' });
    }
    return;
  }

  const respuesta = data.value as RespuestaAjusteLote | null;
  if (!respuesta) {
    $q.notify({ type: 'negative', message: 'No se pudo registrar el ajuste' });
    return;
  }

  $q.notify({ type: 'positive', message: `${etiquetaCampo(campo.value)} ajustada` });
  emit('guardado', respuesta.lote);
  cerrar();
};
</script>

<template>
  <q-dialog :model-value="modelValue" @update:model-value="emit('update:modelValue', $event)">
    <q-card class="dialogo-ajuste">
      <q-card-section class="row items-start no-wrap">
        <div>
          <div class="text-h6">Ajustar dato</div>
          <div class="text-caption text-secundario">
            <span class="codigo">{{ lote.codigo }}</span> · {{ titulo }}
          </div>
        </div>
        <q-space />
        <q-btn flat round dense icon="close" aria-label="Cerrar" @click="cerrar" />
      </q-card-section>

      <q-card-section class="q-pt-none">
        <q-banner
          v-for="mensaje in [
            ...(errores.lote_area_id ?? []),
            ...(errores.lote_insumo_id ?? []),
            ...(errores.lote ?? []),
          ]"
          :key="mensaje"
          dense
          rounded
          class="bg-red-1 text-negative q-mb-md"
        >
          {{ mensaje }}
        </q-banner>

        <q-select
          :model-value="campo"
          :options="campos"
          option-label="etiqueta"
          option-value="campo"
          emit-value
          map-options
          label="Dato a corregir"
          outlined
          :error="Boolean(error('campo'))"
          :error-message="error('campo')"
          class="q-mb-md"
          @update:model-value="cambiarCampo"
        />

        <div class="text-caption text-secundario q-mb-xs">
          Valor actual: <span class="texto-normal">{{ actualFormateado }}</span>
        </div>

        <q-input
          v-if="definicion?.tipo === 'fecha'"
          v-model="valor"
          type="datetime-local"
          label="Valor nuevo"
          outlined
          :error="Boolean(error('valor'))"
          :error-message="error('valor')"
        />
        <q-input
          v-else
          v-model="valor"
          type="number"
          :step="definicion?.tipo === 'entero' ? 1 : 0.001"
          min="0"
          label="Valor nuevo"
          outlined
          :error="Boolean(error('valor'))"
          :error-message="error('valor')"
        />

        <q-input
          v-model="motivo"
          type="textarea"
          autogrow
          label="Motivo"
          outlined
          class="q-mt-md"
          :error="Boolean(error('motivo'))"
          :error-message="error('motivo')"
        />

        <div class="text-caption text-secundario q-mt-sm">
          El lote conserva su estado y sus piezas se corrigen si es la última parada.
        </div>
      </q-card-section>

      <q-card-actions align="right" class="q-px-md q-pb-md">
        <q-btn flat no-caps color="primary" label="Volver" @click="cerrar" />
        <q-btn
          unelevated
          no-caps
          color="primary"
          icon="tune"
          label="Guardar ajuste"
          :loading="guardando"
          :disable="!definicion || valor === '' || !motivo.trim()"
          @click="guardar"
        />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>

<style scoped>
.dialogo-ajuste {
  width: 540px;
  max-width: 92vw;
}

.texto-normal {
  color: inherit;
  font-weight: 500;
}
</style>
