<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import type { Lote } from '@aleacion-prima/types';
import { useApi } from '@/services/api';
import { conPlural } from '@/composables/formato';
import { actualizarEnLista, useCanalesDeLotes, useLotesEnVivo } from '@/composables/lotesEnVivo';
import TarjetaLoteTablero from '@/components/lotes/TarjetaLoteTablero.vue';
import EstadoVacio from '@/components/comunes/EstadoVacio.vue';

const lotes = ref<Lote[]>([]);
const cargando = ref(true);
const error = ref(false);

const enTablero = (lote: Lote): boolean =>
  lote.estado === 'creado' || lote.estado === 'en_proceso' || lote.estado === 'por_confirmar';

const resumen = computed(() => {
  const enProceso = lotes.value.filter((lote) => lote.estado === 'en_proceso').length;
  const porConfirmar = lotes.value.filter((lote) => lote.estado === 'por_confirmar').length;
  return `${enProceso} ${conPlural(enProceso, 'lote', 'lotes')} en proceso · ${porConfirmar} por confirmar`;
});

const cargar = async (): Promise<void> => {
  cargando.value = true;
  const respuesta = await useApi<Lote[]>('/tablero').json<Lote[]>();
  cargando.value = false;
  error.value = Boolean(respuesta.error.value);
  lotes.value = respuesta.data.value ?? [];
};

const { recientes } = useLotesEnVivo(useCanalesDeLotes(), async (evento) => {
  await actualizarEnLista(lotes, evento.codigo);
  lotes.value = lotes.value.filter(enTablero);
});

onMounted(cargar);
</script>

<template>
  <q-page padding>
    <div class="row items-baseline justify-between q-gutter-x-md q-mb-lg">
      <h1 class="text-h4 q-my-none">Estado general de la planta</h1>
      <span v-if="lotes.length" class="text-secundario text-caption">{{ resumen }}</span>
    </div>

    <div v-if="cargando" class="q-pa-xl flex flex-center">
      <q-spinner color="primary" size="40px" />
    </div>

    <EstadoVacio v-else-if="error" icono="cloud_off" titulo="No se pudo cargar el tablero">
      <q-btn flat no-caps color="primary" label="Reintentar" @click="cargar()" />
    </EstadoVacio>

    <EstadoVacio
      v-else-if="!lotes.length"
      titulo="Sin lotes en proceso"
      descripcion="Aquí se verá el recorrido de cada lote en vivo"
      icono="timeline"
    />

    <div v-else class="tablero">
      <TarjetaLoteTablero
        v-for="lote in lotes"
        :key="lote.codigo"
        :lote="lote"
        :class="{ destello: recientes.includes(lote.codigo) }"
      />
    </div>
  </q-page>
</template>

<style scoped lang="scss">
.tablero {
  display: grid;
  grid-template-columns: minmax(0, 1fr);
  gap: 20px;
}
</style>
