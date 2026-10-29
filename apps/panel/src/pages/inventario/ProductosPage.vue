<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { useQuasar } from 'quasar';
import type { ProductoInventario } from '@aleacion-prima/types';
import { useApi } from '@/services/api';

const $q = useQuasar();
const productos = ref<ProductoInventario[]>([]);
const cargando = ref(true);

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
    <div class="text-h4 q-mb-lg">Existencias de producto terminado</div>
    <q-table
      flat
      bordered
      :rows="productos"
      :columns="[
        { name: 'codigo', label: 'Código', field: 'codigo', align: 'left' },
        { name: 'nombre', label: 'Producto', field: 'nombre', align: 'left' },
        { name: 'existencia', label: 'Existencia', field: 'existencia', align: 'right' },
      ]"
      row-key="id"
      :loading="cargando"
    />
  </q-page>
</template>
