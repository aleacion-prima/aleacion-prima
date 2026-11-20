<script setup lang="ts">
import type { ProductoPublico } from '@aleacion-prima/types';
import { material } from '../../composables/especificaciones';

defineProps<{ producto: ProductoPublico }>();
</script>

<template>
  <article class="tarjeta">
    <div class="tarjeta__imagen">
      <img src="/logo.svg" width="28" height="28" alt="" />
    </div>
    <h3 class="tarjeta__nombre">{{ producto.nombre }}</h3>
    <p class="tarjeta__material">{{ material(producto) }}</p>
    <a class="boton boton--contorno" :href="`/producto?id=${producto.id}`">Ver más</a>
  </article>
</template>

<style scoped>
.tarjeta {
  display: flex;
  flex-direction: column;
  gap: 8px;
  padding: 16px;
  background: var(--ap-superficie);
  border: 1px solid var(--ap-borde);
  border-radius: 12px;
}

.tarjeta__imagen {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 144px;
  background: var(--ap-borde);
  border-radius: 8px;
}

.tarjeta__imagen img {
  opacity: 0.35;
  filter: grayscale(1);
}

.tarjeta__nombre {
  margin: 4px 0 0;
  font-family: var(--fuente-titulos);
  font-size: 20px;
  font-weight: 600;
  line-height: 24px;
  color: var(--q-dark);
}

.tarjeta__material {
  margin: 0 0 8px;
  font-size: 12px;
  line-height: 16px;
  color: var(--ap-texto-secundario);
}
</style>
