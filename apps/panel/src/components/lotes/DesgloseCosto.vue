<script setup lang="ts">
import { ref, watch } from 'vue';
import type { CostoLote } from '@aleacion-prima/types';
import { formatearMoneda } from '@/composables/formato';
import { useApi } from '@/services/api';
import EstadoVacio from '@/components/comunes/EstadoVacio.vue';

const props = defineProps<{ codigo: string; version: number }>();

const costo = ref<CostoLote | null>(null);
const cargando = ref(true);
const fallo = ref(false);

let solicitud = 0;

const cargar = async (): Promise<void> => {
  const propia = ++solicitud;
  const { data, error } = await useApi<CostoLote>(
    `/lotes/${encodeURIComponent(props.codigo)}/costo`,
  ).json<CostoLote>();
  // Una respuesta vieja que llega tarde no pisa a la más reciente.
  if (propia !== solicitud) return;
  cargando.value = false;
  // Si la recarga en segundo plano falla, se conserva lo que ya se muestra.
  if (error.value) {
    fallo.value = costo.value === null;
    return;
  }
  fallo.value = false;
  costo.value = data.value;
};

watch(
  () => [props.codigo, props.version],
  () => void cargar(),
  { immediate: true },
);
</script>

<template>
  <q-card flat bordered>
    <q-card-section>
      <div v-if="cargando" class="q-pa-lg flex flex-center">
        <q-spinner color="primary" size="32px" />
      </div>

      <EstadoVacio
        v-else-if="fallo || !costo"
        icono="cloud_off"
        titulo="No se pudo cargar el costo"
      >
        <q-btn flat color="primary" label="Reintentar" @click="cargar" />
      </EstadoVacio>

      <template v-else>
        <div class="row items-center justify-between q-mb-md">
          <h2 class="text-h6 q-my-none">
            {{ costo.congelado ? 'Costo final' : 'Costo a la fecha' }}
          </h2>
          <q-chip
            dense
            :color="costo.congelado ? 'positive' : 'warning'"
            text-color="white"
            :label="costo.congelado ? 'Congelado' : 'En curso'"
          />
        </div>

        <q-markup-table flat separator="horizontal">
          <tbody>
            <tr>
              <td>Material</td>
              <td class="text-right codigo">{{ formatearMoneda(costo.material) }}</td>
            </tr>
            <tr>
              <td>Mano de obra</td>
              <td class="text-right codigo">{{ formatearMoneda(costo.mano_obra) }}</td>
            </tr>
            <tr>
              <td>Máquina</td>
              <td class="text-right codigo">{{ formatearMoneda(costo.maquina) }}</td>
            </tr>
            <tr class="text-weight-bold">
              <td>Total</td>
              <td class="text-right codigo">{{ formatearMoneda(costo.total) }}</td>
            </tr>
          </tbody>
        </q-markup-table>

        <div class="q-mt-md">
          <div class="text-overline text-secundario">Costo por pieza</div>
          <div class="text-h6 codigo">
            {{ costo.por_pieza === null ? '—' : formatearMoneda(costo.por_pieza) }}
          </div>
          <div v-if="costo.por_pieza === null" class="text-caption text-secundario">
            {{
              costo.congelado
                ? 'El lote no terminó piezas.'
                : 'Se calcula al confirmar el cierre del lote.'
            }}
          </div>
        </div>
      </template>
    </q-card-section>
  </q-card>
</template>
