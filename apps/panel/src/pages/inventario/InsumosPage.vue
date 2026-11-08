<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { useQuasar } from 'quasar';
import type { QTableColumn } from 'quasar';
import type { Insumo, MovimientoInsumo } from '@aleacion-prima/types';
import { ESTILOS_ESTADO_INSUMO, estadoInsumo } from '@/catalogos/inventario';
import type { EstadoInsumo } from '@/catalogos/inventario';
import { formatearCantidad, formatearMoneda } from '@/composables/formato';
import PaginacionTabla from '@/components/comunes/PaginacionTabla.vue';
import { useApi } from '@/services/api';
import { useSesionStore } from '@/stores/sesion';

interface FormularioEntrada {
  insumo_id: number | null;
  cantidad: string;
  costo_unitario: string;
  proveedor: string;
  lote_proveedor: string;
}

interface FormularioAjuste {
  cantidad_real: string;
  motivo: string;
}

type FiltroTab = 'todos' | EstadoInsumo;

const $q = useQuasar();
const sesion = useSesionStore();
const insumos = ref<Insumo[]>([]);
const cargando = ref(true);
const dialogo = ref(false);
const dialogoAjuste = ref(false);
const insumoSeleccionado = ref<Insumo | null>(null);
const filtroTab = ref<FiltroTab>('todos');
const busqueda = ref('');
const unidad = ref<string | null>(null);
const formulario = reactive<FormularioEntrada>({
  insumo_id: null,
  cantidad: '',
  costo_unitario: '',
  proveedor: '',
  lote_proveedor: '',
});
const formularioAjuste = reactive<FormularioAjuste>({
  cantidad_real: '',
  motivo: '',
});

const paginacion = ref({ page: 1, rowsPerPage: 10 });

const puedeEditar = computed(() => sesion.usuario?.rol === 'administrador');

const columnas = computed<QTableColumn<Insumo>[]>(() => {
  const base: QTableColumn<Insumo>[] = [
    { name: 'codigo', label: 'Código', field: 'codigo', align: 'left' },
    { name: 'nombre', label: 'Insumo', field: 'nombre', align: 'left' },
    { name: 'unidad', label: 'Unidad', field: 'unidad', align: 'left' },
    { name: 'costo_unitario', label: 'Costo unitario', field: 'costo_unitario', align: 'right' },
    { name: 'stock_minimo', label: 'Stock mínimo', field: 'stock_minimo', align: 'right' },
    { name: 'existencia', label: 'Existencia', field: 'existencia', align: 'right' },
    { name: 'estado', label: 'Estado', field: 'id', align: 'left' },
  ];

  if (!puedeEditar.value) return base;

  return [...base, { name: 'acciones', label: 'Acción', field: 'id', align: 'right' }];
});

watch([filtroTab, busqueda, unidad], () => {
  paginacion.value = { ...paginacion.value, page: 1 };
});

const conteos = computed<Record<FiltroTab, number>>(() => {
  const totales: Record<FiltroTab, number> = {
    todos: insumos.value.length,
    critico: 0,
    por_agotarse: 0,
    optimo: 0,
  };

  for (const insumo of insumos.value) {
    totales[estadoInsumo(insumo)] += 1;
  }

  return totales;
});

const unidades = computed(() =>
  [...new Set(insumos.value.map((insumo) => insumo.unidad))].sort((a, b) => a.localeCompare(b)),
);

const filas = computed(() => {
  const termino = busqueda.value.trim().toLowerCase();

  return insumos.value.filter((insumo) => {
    if (filtroTab.value !== 'todos' && estadoInsumo(insumo) !== filtroTab.value) return false;
    if (unidad.value !== null && insumo.unidad !== unidad.value) return false;
    if (!termino) return true;

    return (
      insumo.codigo.toLowerCase().includes(termino) || insumo.nombre.toLowerCase().includes(termino)
    );
  });
});

const cargar = async (): Promise<void> => {
  cargando.value = true;
  const { data, error } = await useApi<Insumo[]>('/inventario/insumos').json<Insumo[]>();
  cargando.value = false;

  if (error.value) {
    $q.notify({ type: 'negative', message: 'No se pudieron cargar las existencias' });
    return;
  }

  insumos.value = data.value ?? [];
};

const actualizarCosto = (insumoId: number | null): void => {
  const insumo = insumos.value.find((candidato) => candidato.id === insumoId);
  formulario.costo_unitario = insumo?.costo_unitario ?? '';
};

const abrirEntrada = (insumo?: Insumo): void => {
  formulario.insumo_id = insumo?.id ?? null;
  formulario.cantidad = '';
  formulario.costo_unitario = insumo?.costo_unitario ?? '';
  formulario.proveedor = '';
  formulario.lote_proveedor = '';
  dialogo.value = true;
};

const registrarEntrada = async (): Promise<void> => {
  const insumo = insumos.value.find((candidato) => candidato.id === formulario.insumo_id);
  if (!insumo) {
    $q.notify({ type: 'warning', message: 'Elige el insumo que entra al almacén' });
    return;
  }

  const { error } = await useApi(`/insumos/${insumo.id}/entradas`)
    .post({
      cantidad: Number(formulario.cantidad),
      costo_unitario: Number(formulario.costo_unitario),
      proveedor: formulario.proveedor,
      lote_proveedor: formulario.lote_proveedor || null,
    })
    .json<MovimientoInsumo>();

  if (error.value) {
    $q.notify({ type: 'negative', message: 'No se pudo registrar la entrada' });
    return;
  }

  dialogo.value = false;
  await cargar();
  $q.notify({ type: 'positive', message: 'Entrada registrada' });
};

const abrirAjuste = (insumo: Insumo): void => {
  insumoSeleccionado.value = insumo;
  formularioAjuste.cantidad_real = insumo.existencia;
  formularioAjuste.motivo = '';
  dialogoAjuste.value = true;
};

const registrarAjuste = async (): Promise<void> => {
  const insumo = insumoSeleccionado.value;
  if (!insumo) return;

  const { error } = await useApi(`/insumos/${insumo.id}/ajustes`)
    .post({
      cantidad_real: Number(formularioAjuste.cantidad_real),
      motivo: formularioAjuste.motivo,
    })
    .json();

  if (error.value) {
    $q.notify({ type: 'negative', message: 'No se pudo registrar el ajuste' });
    return;
  }

  dialogoAjuste.value = false;
  await cargar();
  $q.notify({ type: 'positive', message: 'Ajuste registrado' });
};

onMounted(cargar);
</script>

<template>
  <q-page padding>
    <div class="row items-start justify-between q-mb-lg">
      <div>
        <div class="text-h4">Inventario de insumos</div>
        <div class="text-secundario q-mt-xs">Existencia contra stock mínimo</div>
      </div>
      <q-btn
        v-if="puedeEditar"
        color="primary"
        unelevated
        icon="add"
        label="Registrar entrada"
        @click="abrirEntrada()"
      />
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
        <q-tab name="critico" :label="`Críticos (${conteos.critico})`" />
        <q-tab name="por_agotarse" :label="`Por agotarse (${conteos.por_agotarse})`" />
        <q-tab name="optimo" :label="`Óptimos (${conteos.optimo})`" />
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
            v-model="unidad"
            :options="unidades"
            label="Unidad"
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
        no-data-label="No hay insumos que coincidan con el filtro"
        :rows-per-page-options="[10, 25, 50]"
      >
        <template #body-cell-codigo="{ row }">
          <q-td>
            <span class="codigo">{{ row.codigo }}</span>
          </q-td>
        </template>
        <template #body-cell-costo_unitario="{ row }">
          <q-td class="text-right">{{ formatearMoneda(row.costo_unitario) }}</q-td>
        </template>
        <template #body-cell-stock_minimo="{ row }">
          <q-td class="text-right">{{ formatearCantidad(row.stock_minimo) }}</q-td>
        </template>
        <template #body-cell-existencia="{ row }">
          <q-td class="text-right text-weight-medium">{{ formatearCantidad(row.existencia) }}</q-td>
        </template>
        <template #body-cell-estado="{ row }">
          <q-td>
            <q-chip
              dense
              :color="ESTILOS_ESTADO_INSUMO[estadoInsumo(row)].color"
              text-color="white"
              :label="ESTILOS_ESTADO_INSUMO[estadoInsumo(row)].etiqueta"
              class="text-weight-medium"
            />
          </q-td>
        </template>
        <template #body-cell-acciones="{ row }">
          <q-td class="text-right">
            <q-btn
              v-if="puedeEditar"
              flat
              dense
              no-caps
              color="secondary"
              label="Ajustar"
              @click="abrirAjuste(row)"
            />
          </q-td>
        </template>

        <template #bottom>
          <PaginacionTabla
            v-model:pagina="paginacion.page"
            :por-pagina="paginacion.rowsPerPage"
            :total="filas.length"
            sustantivo="insumos"
          />
        </template>
      </q-table>
    </q-card>

    <q-dialog v-model="dialogo">
      <q-card style="width: 520px; max-width: 90vw">
        <q-card-section class="text-h6">Registrar entrada</q-card-section>
        <q-card-section class="column q-gutter-md">
          <q-select
            v-model="formulario.insumo_id"
            :options="insumos"
            option-label="nombre"
            option-value="id"
            label="Insumo"
            emit-value
            map-options
            outlined
            @update:model-value="actualizarCosto"
          />
          <q-input v-model="formulario.cantidad" label="Cantidad" type="number" outlined />
          <q-input
            v-model="formulario.costo_unitario"
            label="Costo unitario de compra"
            type="number"
            outlined
          />
          <q-input v-model="formulario.proveedor" label="Proveedor" outlined />
          <q-input v-model="formulario.lote_proveedor" label="Lote del proveedor" outlined />
        </q-card-section>
        <q-card-actions align="right">
          <q-btn v-close-popup flat label="Cancelar" />
          <q-btn color="primary" unelevated label="Guardar entrada" @click="registrarEntrada" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <q-dialog v-model="dialogoAjuste">
      <q-card style="width: 520px; max-width: 90vw">
        <q-card-section class="text-h6">
          Ajustar existencia de {{ insumoSeleccionado?.nombre }}
        </q-card-section>
        <q-card-section class="column q-gutter-md">
          <q-input
            v-model="formularioAjuste.cantidad_real"
            label="Cantidad real"
            type="number"
            min="0"
            outlined
          />
          <q-input v-model="formularioAjuste.motivo" label="Motivo" type="textarea" outlined />
        </q-card-section>
        <q-card-actions align="right">
          <q-btn v-close-popup flat label="Cancelar" />
          <q-btn color="secondary" unelevated label="Guardar ajuste" @click="registrarAjuste" />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>
