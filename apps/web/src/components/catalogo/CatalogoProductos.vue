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
    .filter((producto) => producto.disponible)
    .sort((a, b) => (b.cantidad ?? 0) - (a.cantidad ?? 0) || a.nombre.localeCompare(b.nombre)),
);
const sobrePedido = computed(() =>
  productos.value
    .filter((producto) => !producto.disponible)
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
      producto.id === evento.id
        ? { ...producto, disponible: evento.disponible, cantidad: evento.cantidad }
        : producto,
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
      <h2 class="grupo__titulo">
        Disponibles <span>{{ disponibles.length }}</span>
      </h2>
      <div class="cuadricula">
        <TarjetaProducto
          v-for="(producto, i) in disponibles"
          :key="producto.id"
          :producto="producto"
          :style="{ '--i': i }"
        />
      </div>
    </section>
    <section v-if="sobrePedido.length" class="grupo">
      <h2 class="grupo__titulo">
        Sobre pedido <span>{{ sobrePedido.length }}</span>
      </h2>
      <div class="cuadricula">
        <TarjetaProducto
          v-for="(producto, i) in sobrePedido"
          :key="producto.id"
          :producto="producto"
          :style="{ '--i': i + disponibles.length }"
        />
      </div>
    </section>
  </template>
</template>

<style scoped>
.grupo + .grupo {
  margin-top: 56px;
}

.grupo__titulo {
  display: flex;
  align-items: center;
  gap: 10px;
  margin: 0 0 20px;
  padding-left: 14px;
  border-left: 4px solid var(--q-primary);
  font-family: var(--fuente-titulos);
  font-size: 26px;
  font-weight: 700;
  line-height: 30px;
  text-transform: uppercase;
  color: var(--q-dark);
}

.grupo__titulo span {
  padding: 0 8px;
  border-radius: 999px;
  background: var(--ap-borde);
  font-family: Roboto, sans-serif;
  font-size: 13px;
  font-weight: 500;
  line-height: 22px;
  color: var(--ap-texto-secundario);
}

@media (prefers-reduced-motion: no-preference) {
  .cuadricula > * {
    animation: subir 0.6s cubic-bezier(0.2, 0.7, 0.2, 1) backwards;
    animation-delay: calc(var(--i, 0) * 80ms + 0.15s);
  }
}

@keyframes subir {
  from {
    opacity: 0;
    transform: translateY(24px);
  }
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
