<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import type { ProductoPublico } from '@aleacion-prima/types';
import { listarProductos } from '../../services/api';
import { useCatalogoEnVivo } from '../../composables/catalogoEnVivo';
import TarjetaProducto from './TarjetaProducto.vue';

const productos = ref<ProductoPublico[]>([]);
const estado = ref<'cargando' | 'listo' | 'error'>('cargando');

const disponibles = computed(() =>
  productos.value
    .filter((producto) => producto.disponible > 0)
    .sort((a, b) => b.disponible - a.disponible || a.nombre.localeCompare(b.nombre)),
);
const sobrePedido = computed(() =>
  productos.value
    .filter((producto) => producto.disponible <= 0)
    .sort((a, b) => a.nombre.localeCompare(b.nombre)),
);

const cargar = async (): Promise<void> => {
  const respuesta = await listarProductos();
  estado.value = respuesta ? 'listo' : 'error';
  productos.value = respuesta ?? [];
};

onMounted(cargar);
useCatalogoEnVivo({
  catalogo: () => void cargar(),
  disponibilidad: (evento) => {
    productos.value = productos.value.map((producto) =>
      producto.id === evento.id ? { ...producto, disponible: evento.disponible } : producto,
    );
  },
});
</script>

<template>
  <p v-if="estado === 'cargando'" class="aviso">Cargando productos…</p>
  <p v-else-if="estado === 'error'" class="aviso">No se pudo cargar el catálogo.</p>
  <p v-else-if="!productos.length" class="aviso">Aún no hay productos publicados.</p>
  <template v-else>
    <section v-if="disponibles.length" class="grupo">
      <h2 class="grupo__titulo">Disponibles</h2>
      <div class="cuadricula">
        <TarjetaProducto v-for="producto in disponibles" :key="producto.id" :producto="producto" />
      </div>
    </section>
    <section v-if="sobrePedido.length" class="grupo">
      <h2 class="grupo__titulo">Sobre pedido</h2>
      <div class="cuadricula">
        <TarjetaProducto v-for="producto in sobrePedido" :key="producto.id" :producto="producto" />
      </div>
    </section>
  </template>
</template>

<style scoped>
.grupo + .grupo {
  margin-top: 40px;
}

.grupo__titulo {
  margin: 0 0 16px;
  font-family: var(--fuente-titulos);
  font-size: 24px;
  font-weight: 600;
  line-height: 28px;
  color: var(--q-dark);
}

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
