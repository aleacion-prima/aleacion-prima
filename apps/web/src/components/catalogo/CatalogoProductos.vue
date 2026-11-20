<script setup lang="ts">
import { onMounted, ref } from 'vue';
import type { ProductoPublico } from '@aleacion-prima/types';
import { listarProductos } from '../../services/api';
import { useCatalogoEnVivo } from '../../composables/catalogoEnVivo';
import TarjetaProducto from './TarjetaProducto.vue';

const productos = ref<ProductoPublico[]>([]);
const estado = ref<'cargando' | 'listo' | 'error'>('cargando');

const cargar = async (): Promise<void> => {
  const respuesta = await listarProductos();
  estado.value = respuesta ? 'listo' : 'error';
  productos.value = respuesta ?? [];
};

onMounted(cargar);
useCatalogoEnVivo({ catalogo: () => void cargar() });
</script>

<template>
  <p v-if="estado === 'cargando'" class="aviso">Cargando productos…</p>
  <p v-else-if="estado === 'error'" class="aviso">No se pudo cargar el catálogo.</p>
  <p v-else-if="!productos.length" class="aviso">Aún no hay productos publicados.</p>
  <div v-else class="cuadricula">
    <TarjetaProducto v-for="producto in productos" :key="producto.id" :producto="producto" />
  </div>
</template>

<style scoped>
.cuadricula {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 24px;
}

@media (max-width: 1023px) {
  .cuadricula {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 599px) {
  .cuadricula {
    grid-template-columns: 1fr;
  }
}
</style>
