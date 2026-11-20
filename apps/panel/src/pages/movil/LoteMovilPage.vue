<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import type { Lote } from '@aleacion-prima/types';
import { useApi } from '@/services/api';
import { recordarEscaneado } from '@/services/escaneados';
import { estadoDeParada, paradasDeLote } from '@/composables/recorridoLote';
import { useCanalesDeLotes, useLotesEnVivo } from '@/composables/lotesEnVivo';
import ChipEstado from '@/components/lotes/ChipEstado.vue';
import RecorridoLote from '@/components/lotes/RecorridoLote.vue';
import EstadoVacio from '@/components/comunes/EstadoVacio.vue';

const route = useRoute();

const lote = ref<Lote | null>(null);
const cargando = ref(true);
const noEncontrado = ref(false);

const fecha = new Intl.DateTimeFormat('es-MX', {
  day: 'numeric',
  month: 'short',
  hour: '2-digit',
  minute: '2-digit',
  hourCycle: 'h23',
});

const ubicacion = computed(() => {
  const enCurso = lote.value?.paradas.find((parada) => estadoDeParada(parada) === 'en_curso');
  if (!enCurso?.entrada_ocurrido_en) return '';
  return `En ${enCurso.area.nombre} desde el ${fecha.format(new Date(enCurso.entrada_ocurrido_en))}`;
});

const cargar = async (conSpinner = true): Promise<void> => {
  cargando.value = conSpinner;
  const codigo = String(route.params.codigo ?? '');
  const { data, error, statusCode } = await useApi<Lote>(
    `/lotes/${encodeURIComponent(codigo)}`,
  ).json<Lote>();
  noEncontrado.value = Boolean(error.value) && statusCode.value === 404;
  lote.value = error.value ? null : data.value;
  cargando.value = false;
  if (lote.value) {
    void recordarEscaneado({
      codigo: lote.value.codigo,
      producto: lote.value.producto.nombre,
      estado: lote.value.estado,
      consultado_en: new Date().toISOString(),
    });
  }
};

watch(
  () => route.params.codigo,
  () => cargar(),
  { immediate: true },
);

useLotesEnVivo(useCanalesDeLotes(), (evento) => {
  if (evento.codigo === lote.value?.codigo) void cargar(false);
});
</script>

<template>
  <q-page class="lote-movil">
    <div v-if="cargando" class="q-pa-xl flex flex-center">
      <q-spinner color="primary" size="40px" />
    </div>

    <EstadoVacio
      v-else-if="!lote"
      :icono="noEncontrado ? 'search_off' : 'cloud_off'"
      :titulo="noEncontrado ? 'Ese lote no existe' : 'No se pudo cargar el lote'"
    >
      <q-btn
        v-if="noEncontrado"
        unelevated
        no-caps
        color="primary"
        icon="qr_code_scanner"
        label="Escanear otro"
        to="/escanear"
      />
      <q-btn v-else flat no-caps color="primary" label="Reintentar" @click="cargar()" />
    </EstadoVacio>

    <template v-else>
      <q-card flat bordered class="lote-movil__resumen">
        <div class="row items-center justify-between no-wrap q-gutter-x-sm">
          <div class="text-h6 ellipsis">{{ lote.producto.nombre }}</div>
          <ChipEstado :estado="lote.estado" />
        </div>
        <div class="text-secundario">
          {{ lote.cantidad_objetivo }} piezas ·
          <span class="codigo">{{ lote.producto.codigo }}</span>
        </div>
        <div v-if="ubicacion" class="row items-center no-wrap q-gutter-x-sm q-mt-sm">
          <q-icon name="handyman" color="accent" size="20px" />
          <span>{{ ubicacion }}</span>
        </div>
      </q-card>

      <div class="lote-movil__titulo">Recorrido</div>
      <RecorridoLote :paradas="paradasDeLote(lote)" vertical />
    </template>
  </q-page>
</template>

<style scoped lang="scss">
.lote-movil {
  padding: 16px;
}

.lote-movil__resumen {
  padding: 16px;
  border-radius: 12px;
}

.lote-movil__titulo {
  margin: 24px 0 8px;
  font-size: 12px;
  font-weight: 600;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: $grey-7;
}
</style>
