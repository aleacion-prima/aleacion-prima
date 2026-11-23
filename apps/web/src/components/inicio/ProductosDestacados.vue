<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import type { ProductoPublico } from '@aleacion-prima/types';
import { listarProductos } from '../../services/api';
import { useCatalogoEnVivo } from '../../composables/catalogoEnVivo';
import CuadriculaTarjetas from '../catalogo/CuadriculaTarjetas.vue';
import TarjetaProducto from '../catalogo/TarjetaProducto.vue';

const productos = ref<ProductoPublico[]>([]);
const estado = ref<'cargando' | 'listo' | 'error'>('cargando');

const destacados = computed(() =>
  [...productos.value]
    .sort(
      (a, b) =>
        Number(b.disponible) - Number(a.disponible) ||
        (b.cantidad ?? 0) - (a.cantidad ?? 0) ||
        a.nombre.localeCompare(b.nombre),
    )
    .slice(0, 3),
);

const cargar = async (): Promise<void> => {
  const respuesta = await listarProductos();
  estado.value = respuesta ? 'listo' : 'error';
  productos.value = respuesta ?? [];
};

onMounted(cargar);
useCatalogoEnVivo({
  catalogo: () => void cargar(),
  disponibilidad: () => void cargar(),
});
</script>

<template>
  <p v-if="estado === 'cargando'" class="aviso">Cargando productos…</p>
  <p v-else-if="estado === 'error'" class="aviso">No se pudo cargar el catálogo.</p>
  <p v-else-if="!destacados.length" class="aviso">Aún no hay productos publicados.</p>
  <CuadriculaTarjetas v-else>
    <TarjetaProducto
      v-for="(producto, i) in destacados"
      :key="producto.id"
      :producto="producto"
      :style="{ '--i': i }"
    />
  </CuadriculaTarjetas>
</template>
