<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import { useQuasar } from 'quasar';
import type { QTableColumn } from 'quasar';
import type { ProductoInventario } from '@aleacion-prima/types';
import { ESTILOS_ESTADO_PRODUCTO, estadoProducto, formatearPeso } from '@/catalogos/inventario';
import type { EstadoProducto } from '@/catalogos/inventario';
import { formatearCantidad } from '@/composables/formato';
import PaginacionTabla from '@/components/comunes/PaginacionTabla.vue';
import { useApi } from '@/services/api';

type FiltroTab = 'todos' | EstadoProducto;

const $q = useQuasar();
const productos = ref<ProductoInventario[]>([]);
const cargando = ref(true);
const filtroTab = ref<FiltroTab>('todos');
const busqueda = ref('');
const material = ref<string | null>(null);
const paginacion = ref({ page: 1, rowsPerPage: 10 });

watch([filtroTab, busqueda, material], () => {
  paginacion.value = { ...paginacion.value, page: 1 };
});

const columnas: QTableColumn<ProductoInventario>[] = [
  { name: 'codigo', label: 'Código', field: 'codigo', align: 'left' },
  { name: 'nombre', label: 'Producto', field: 'nombre', align: 'left' },
  { name: 'material', label: 'Material', field: 'material', align: 'left' },
  { name: 'peso', label: 'Peso', field: 'peso', align: 'right' },
  { name: 'existencia', label: 'Existencia', field: 'existencia', align: 'right' },
  { name: 'estado', label: 'Estado', field: 'id', align: 'left' },
];

const conteos = computed<Record<FiltroTab, number>>(() => {
  const totales: Record<FiltroTab, number> = {
    todos: productos.value.length,
    disponible: 0,
    agotado: 0,
  };

  for (const producto of productos.value) {
    totales[estadoProducto(producto)] += 1;
  }

  return totales;
});

const materiales = computed(() =>
  [
    ...new Set(
      productos.value
        .map((producto) => producto.material)
        .filter((valor): valor is string => valor !== null),
    ),
  ].sort((a, b) => a.localeCompare(b)),
);

const filas = computed(() => {
  const termino = busqueda.value.trim().toLowerCase();

  return productos.value.filter((producto) => {
    if (filtroTab.value !== 'todos' && estadoProducto(producto) !== filtroTab.value) return false;
    if (material.value !== null && producto.material !== material.value) return false;
    if (!termino) return true;

    return (
      producto.codigo.toLowerCase().includes(termino) ||
      producto.nombre.toLowerCase().includes(termino)
    );
  });
});

const cargar = async (): Promise<void> => {
  cargando.value = true;
  const { data, error } =
    await useApi<ProductoInventario[]>('/inventario/productos').json<ProductoInventario[]>();
  cargando.value = false;

  if (error.value) {
    $q.notify({ type: 'negative', message: 'No se pudieron cargar las existencias' });
    return;
  }

  productos.value = data.value ?? [];
};

onMounted(cargar);
</script>

<template>
  <q-page padding>
    <div class="q-mb-lg">
      <div class="text-h4">Inventario de producto terminado</div>
      <div class="text-secundario q-mt-xs">Piezas disponibles para pedidos</div>
    </div>

    <q-card flat bordered>
      <q-tabs
        v-model="filtroTab"
        dense
        no-caps
        align="left"
        active-color="primary"
        indicator-color="primary"
        class="text-secundario"
      >
        <q-tab name="todos" :label="`Todos (${conteos.todos})`" />
        <q-tab name="disponible" :label="`Disponibles (${conteos.disponible})`" />
        <q-tab name="agotado" :label="`Agotados (${conteos.agotado})`" />
      </q-tabs>

      <q-separator />

      <div class="row q-col-gutter-md q-pa-md">
        <div class="col-12 col-md-6">
          <q-input v-model="busqueda" outlined dense clearable placeholder="Buscar en inventario">
            <template #prepend>
              <q-icon name="search" />
            </template>
          </q-input>
        </div>
        <div class="col-12 col-md-4">
          <q-select
            v-model="material"
            :options="materiales"
            label="Material"
            placeholder="Filtro"
            outlined
            dense
            clearable
          >
            <template #prepend>
              <q-icon name="filter_list" />
            </template>
          </q-select>
        </div>
      </div>

      <q-table
        flat
        :rows="filas"
        :columns="columnas"
        row-key="id"
        :loading="cargando"
        v-model:pagination="paginacion"
        no-data-label="No hay producto terminado que coincida con el filtro"
        :rows-per-page-options="[10, 25, 50]"
      >
        <template #body-cell-codigo="{ row }">
          <q-td>
            <span class="codigo">{{ row.codigo }}</span>
          </q-td>
        </template>
        <template #body-cell-material="{ row }">
          <q-td>{{ row.material ?? '—' }}</q-td>
        </template>
        <template #body-cell-peso="{ row }">
          <q-td class="text-right">{{ formatearPeso(row.peso) }}</q-td>
        </template>
        <template #body-cell-existencia="{ row }">
          <q-td class="text-right text-weight-medium">{{ formatearCantidad(row.existencia) }}</q-td>
        </template>
        <template #body-cell-estado="{ row }">
          <q-td>
            <q-chip
              dense
              :color="ESTILOS_ESTADO_PRODUCTO[estadoProducto(row)].color"
              text-color="white"
              :label="ESTILOS_ESTADO_PRODUCTO[estadoProducto(row)].etiqueta"
              class="text-weight-medium"
            />
          </q-td>
        </template>

        <template #bottom>
          <PaginacionTabla
            v-model:pagina="paginacion.page"
            :por-pagina="paginacion.rowsPerPage"
            :total="filas.length"
            sustantivo="productos"
          />
        </template>
      </q-table>
    </q-card>
  </q-page>
</template>
