<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import type { ProductoPublico } from '@aleacion-prima/types';
import { listarProductos } from '../../services/api';
import { useCatalogoEnVivo } from '../../composables/catalogoEnVivo';
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
  <div v-else class="destacados">
    <TarjetaProducto v-for="producto in destacados" :key="producto.id" :producto="producto" />
  </div>
</template>

<style scoped>
.destacados {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 24px;
}

@media (prefers-reduced-motion: no-preference) {
  .destacados > * {
    animation: subir 0.6s cubic-bezier(0.2, 0.7, 0.2, 1) backwards;
  }

  .destacados > :nth-child(2) {
    animation-delay: 0.12s;
  }

  .destacados > :nth-child(3) {
    animation-delay: 0.24s;
  }
}

@keyframes subir {
  from {
    opacity: 0;
    transform: translateY(24px);
  }

  to {
    opacity: 1;
    transform: none;
  }
}

@media (max-width: 1023px) {
  .destacados {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 599px) {
  .destacados {
    grid-template-columns: 1fr;
  }
}
</style>
