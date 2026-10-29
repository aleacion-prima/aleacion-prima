<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue';
import { useQuasar } from 'quasar';
import type { Insumo, MovimientoInsumo } from '@aleacion-prima/types';
import { useApi } from '@/services/api';
import { useSesionStore } from '@/stores/sesion';

interface FormularioEntrada {
  cantidad: string;
  costo_unitario: string;
  proveedor: string;
  lote_proveedor: string;
}

const $q = useQuasar();
const sesion = useSesionStore();
const insumos = ref<Insumo[]>([]);
const cargando = ref(true);
const dialogo = ref(false);
const insumoSeleccionado = ref<Insumo | null>(null);
const formulario = reactive<FormularioEntrada>({
  cantidad: '',
  costo_unitario: '',
  proveedor: '',
  lote_proveedor: '',
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

const abrirEntrada = (insumo: Insumo): void => {
  insumoSeleccionado.value = insumo;
  formulario.cantidad = '';
  formulario.costo_unitario = insumo.costo_unitario;
  formulario.proveedor = '';
  formulario.lote_proveedor = '';
  dialogo.value = true;
};

const registrarEntrada = async (): Promise<void> => {
  const insumo = insumoSeleccionado.value;
  if (!insumo) return;

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

onMounted(cargar);
</script>

<template>
  <q-page padding>
    <div class="text-h4 q-mb-lg">Inventario de insumos</div>

    <q-table
      flat
      bordered
      :rows="insumos"
      :columns="[
        { name: 'codigo', label: 'Código', field: 'codigo', align: 'left' },
        { name: 'nombre', label: 'Insumo', field: 'nombre', align: 'left' },
        { name: 'existencia', label: 'Existencia', field: 'existencia', align: 'right' },
        { name: 'unidad', label: 'Unidad', field: 'unidad', align: 'left' },
        { name: 'stock_minimo', label: 'Stock mínimo', field: 'stock_minimo', align: 'right' },
        { name: 'acciones', label: '', field: 'id', align: 'right' },
      ]"
      row-key="id"
      :loading="cargando"
    >
      <template #body-cell-existencia="{ row }">
        <q-td class="text-right text-weight-medium">
          {{ row.existencia }}
        </q-td>
      </template>
      <template #body-cell-acciones="{ row }">
        <q-td class="text-right">
          <q-btn
            v-if="sesion.usuario?.rol === 'administrador'"
            color="primary"
            flat
            icon="add"
            label="Registrar entrada"
            @click="abrirEntrada(row)"
          />
        </q-td>
      </template>
    </q-table>

    <q-dialog v-model="dialogo">
      <q-card style="width: 520px; max-width: 90vw">
        <q-card-section class="text-h6">
          Entrada de {{ insumoSeleccionado?.nombre }}
        </q-card-section>
        <q-card-section class="column q-gutter-md">
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
  </q-page>
</template>
