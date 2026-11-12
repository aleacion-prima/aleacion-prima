<script setup lang="ts">
import { computed, ref, onMounted } from 'vue';
import { useApi } from '@/services/api';
import { useRoute } from 'vue-router';
import type { Lote } from '@aleacion-prima/types';
import EstadoVacio from '@/components/comunes/EstadoVacio.vue';
import EtiquetaLote from '@/components/lotes/EtiquetaLote.vue';

const MAXIMO_COPIAS = 50;

const route = useRoute();

const lote = ref<Lote | null>(null);
const cargando = ref(true);
const noEncontrado = ref(false);
const copias = ref(1);

const codigo = computed(() => String(route.params.codigo ?? ''));

const totalCopias = computed(() =>
  Math.min(Math.max(Math.trunc(Number(copias.value)) || 1, 1), MAXIMO_COPIAS),
);

const cargar = async (): Promise<void> => {
  const { data, error, statusCode } = await useApi(
    `/lotes/${encodeURIComponent(codigo.value)}`,
  ).json<Lote>();

  cargando.value = false;
  noEncontrado.value = Boolean(error.value) && statusCode.value === 404;
  lote.value = error.value ? null : data.value;
};

const imprimir = (): void => {
  window.print();
};

onMounted(cargar);
</script>

<template>
  <q-page padding>
    <q-btn
      flat
      no-caps
      color="primary"
      icon="arrow_back"
      label="Volver al lote"
      :to="`/lotes/${codigo}`"
      class="q-mb-sm no-imprimir"
    />

    <div v-if="cargando" class="q-pa-xl flex flex-center">
      <q-spinner color="primary" size="40px" />
    </div>

    <EstadoVacio
      v-else-if="!lote"
      :icono="noEncontrado ? 'search_off' : 'cloud_off'"
      :titulo="noEncontrado ? 'Ese lote no existe' : 'No se pudo cargar el lote'"
    />

    <template v-else>
      <div class="row items-end justify-between q-gutter-y-sm q-mb-md no-imprimir">
        <h1 class="text-h4 q-my-none">Etiqueta del lote</h1>
        <div class="row items-end q-gutter-sm">
          <q-input
            v-model.number="copias"
            type="number"
            min="1"
            :max="MAXIMO_COPIAS"
            label="Copias"
            outlined
            dense
            class="campo-copias"
          />
          <q-btn
            unelevated
            no-caps
            color="primary"
            icon="print"
            label="Imprimir"
            @click="imprimir"
          />
        </div>
      </div>

      <q-card flat bordered class="vista-previa">
        <div class="text-caption text-secundario no-imprimir">100 × 60 mm</div>
        <div class="hoja-etiquetas">
          <div v-for="copia in totalCopias" :key="copia" class="pagina-etiqueta">
            <EtiquetaLote :lote="lote" />
          </div>
        </div>
      </q-card>
    </template>
  </q-page>
</template>

<style scoped lang="scss">
.campo-copias {
  width: 120px;
}

.vista-previa {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 24px;
  padding: 48px;
}

.hoja-etiquetas {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 24px;
}
</style>

<style lang="scss">
@media print {
  @page {
    size: 100mm 60mm;
    margin: 0;
  }

  body:has(.hoja-etiquetas) {
    background: white;

    .q-header,
    .q-drawer-container,
    .no-imprimir {
      display: none !important;
    }

    .q-page-container {
      padding: 0 !important;
    }

    .q-layout,
    .q-page {
      min-height: 0 !important;
      padding: 0;
    }

    .vista-previa {
      display: block;
      padding: 0;
      border: none;
      box-shadow: none;
    }

    .hoja-etiquetas {
      display: block;
    }

    .pagina-etiqueta {
      break-after: page;
    }

    .pagina-etiqueta:last-child {
      break-after: auto;
    }
  }
}
</style>
