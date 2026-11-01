<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import type { QTableColumn } from 'quasar';
import type { EstadoLote, Lote } from '@aleacion-prima/types';
import { ESTADOS_LOTE } from '@aleacion-prima/types';
import { useApi } from '@/services/api';
import { useSesionStore } from '@/stores/sesion';
import { ESTILOS_ESTADO_LOTE } from '@/catalogos/estadosLote';
import { estadoDeParada, resumenRecorrido } from '@/composables/recorridoLote';
import { actualizarEnLista, useCanalesDeLotes, useLotesEnVivo } from '@/composables/lotesEnVivo';
import ChipEstado from '@/components/lotes/ChipEstado.vue';
import PasoRecorrido from '@/components/lotes/PasoRecorrido.vue';
import EstadoVacio from '@/components/comunes/EstadoVacio.vue';
import NumeroAnimado from '@/components/comunes/NumeroAnimado.vue';

const router = useRouter();
const sesion = useSesionStore();

const lotes = ref<Lote[]>([]);
const cargando = ref(true);
const error = ref(false);
const pestana = ref<EstadoLote | 'todos'>('todos');

const columnas: QTableColumn<Lote>[] = [
  { name: 'codigo', label: 'Código', field: 'codigo', align: 'left' },
  { name: 'producto', label: 'Producto', field: (lote) => lote.producto.nombre, align: 'left' },
  { name: 'recorrido', label: 'Recorrido', field: 'id', align: 'left' },
  { name: 'estado', label: 'Estado', field: 'estado', align: 'left' },
];

const puedeCrear = computed(() => sesion.usuario?.rol === 'administrador');

const PLURALES: Record<EstadoLote, string> = {
  creado: 'Creados',
  en_proceso: 'En proceso',
  por_confirmar: 'Por confirmar',
  terminado: 'Terminados',
  cancelado: 'Cancelados',
};

const pestanas = computed(() => [
  { valor: 'todos' as const, etiqueta: 'Todos', total: lotes.value.length },
  ...ESTADOS_LOTE.map((estado) => ({
    valor: estado,
    etiqueta: PLURALES[estado],
    total: lotes.value.filter((lote) => lote.estado === estado).length,
  })),
]);

const filas = computed(() =>
  pestana.value === 'todos'
    ? lotes.value
    : lotes.value.filter((lote) => lote.estado === pestana.value),
);

const cargar = async (): Promise<void> => {
  cargando.value = true;
  const respuesta = await useApi<Lote[]>('/lotes').json<Lote[]>();
  cargando.value = false;
  error.value = Boolean(respuesta.error.value);
  lotes.value = respuesta.data.value ?? [];
};

const abrir = (_evento: Event, lote: Lote): void => {
  void router.push(`/lotes/${lote.codigo}`);
};

const { recientes } = useLotesEnVivo(useCanalesDeLotes(), (evento) =>
  actualizarEnLista(lotes, evento.codigo),
);

const claseFila = (lote: Lote): string => (recientes.value.includes(lote.codigo) ? 'destello' : '');

onMounted(() => void cargar());
</script>

<template>
  <q-page padding>
    <div class="row items-center justify-between q-mb-md">
      <h1 class="text-h4 q-my-none">Lotes</h1>
      <q-btn
        v-if="puedeCrear"
        color="primary"
        unelevated
        icon="add"
        label="Nuevo lote"
        to="/lotes/nuevo"
      />
    </div>

    <q-tabs
      v-model="pestana"
      align="left"
      active-color="primary"
      indicator-color="primary"
      no-caps
      class="text-secundario q-mb-md"
    >
      <q-tab v-for="opcion in pestanas" :key="opcion.valor" :name="opcion.valor">
        <span class="text-weight-medium">
          {{ opcion.etiqueta }} · <NumeroAnimado :valor="opcion.total" formato="entero" />
        </span>
      </q-tab>
    </q-tabs>
    <q-separator class="q-mb-md" />

    <q-table
      :key="pestana"
      flat
      bordered
      :rows="filas"
      :columns="columnas"
      row-key="id"
      :loading="cargando"
      :pagination="{ rowsPerPage: 10 }"
      table-header-class="bg-secondary text-white"
      :table-row-class-fn="claseFila"
      class="tabla-lotes"
      @row-click="abrir"
    >
      <template #body-cell-codigo="{ row }">
        <q-td class="codigo">
          <span
            class="tabla-lotes__acento"
            :class="`bg-${ESTILOS_ESTADO_LOTE[row.estado as EstadoLote].color}`"
          />
          {{ row.codigo }}
        </q-td>
      </template>

      <template #body-cell-recorrido="{ row }">
        <q-td>
          <div class="row items-center no-wrap q-gutter-x-xs">
            <PasoRecorrido
              v-for="parada in (row as Lote).paradas"
              :key="parada.id"
              :area="parada.area.codigo"
              :estado="estadoDeParada(parada)"
            />
            <span class="text-caption text-secundario q-ml-sm">{{ resumenRecorrido(row) }}</span>
          </div>
        </q-td>
      </template>

      <template #body-cell-estado="{ row }">
        <q-td>
          <ChipEstado :estado="row.estado" />
        </q-td>
      </template>

      <template #no-data>
        <EstadoVacio
          v-if="error"
          icono="cloud_off"
          titulo="No se pudieron cargar los lotes"
          class="full-width"
        >
          <q-btn flat color="primary" label="Reintentar" @click="cargar()" />
        </EstadoVacio>
        <EstadoVacio
          v-else
          icono="precision_manufacturing"
          :titulo="pestana === 'todos' ? 'Aún no hay lotes' : 'No hay lotes en este estado'"
          class="full-width"
        />
      </template>
    </q-table>
  </q-page>
</template>

<style scoped lang="scss">
.tabla-lotes {
  :deep(tbody tr) {
    cursor: pointer;
    animation: aparecer $duracion-media $curva-salida backwards;

    @for $i from 1 through 10 {
      &:nth-child(#{$i}) {
        animation-delay: #{($i - 1) * 0.04}s;
      }
    }
  }

  :deep(tbody tr:hover) .tabla-lotes__acento {
    width: 5px;
  }

  &__acento {
    position: absolute;
    left: 0;
    top: 8px;
    bottom: 8px;
    width: 3px;
    border-radius: 2px;
    transition: width $duracion-rapida $curva-salida;
  }

  :deep(td.codigo) {
    position: relative;
    padding-left: 20px;
  }
}
</style>
