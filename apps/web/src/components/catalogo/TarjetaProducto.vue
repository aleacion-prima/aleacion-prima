<script setup lang="ts">
import { computed } from 'vue';
import type { ProductoPublico } from '@aleacion-prima/types';
import { datosTecnicos, existencia, material } from '../../composables/especificaciones';

const props = defineProps<{ producto: ProductoPublico }>();

const medidas = computed(() => datosTecnicos(props.producto).filter((dato) => dato.mono));
</script>

<template>
  <article class="tarjeta">
    <a class="tarjeta__enlace" :href="`/producto?id=${producto.id}`">
      <div class="tarjeta__imagen">
        <img src="/logo.svg" width="40" height="40" alt="" />
        <span
          class="tarjeta__existencia"
          :class="{ 'tarjeta__existencia--si': producto.disponible }"
        >
          {{ existencia(producto) }}
        </span>
      </div>
      <div class="tarjeta__cuerpo">
        <h3 class="tarjeta__nombre">{{ producto.nombre }}</h3>
        <p class="tarjeta__material">{{ material(producto) }}</p>
        <dl v-if="medidas.length" class="tarjeta__medidas">
          <div v-for="dato in medidas" :key="dato.etiqueta">
            <dt>{{ dato.etiqueta }}</dt>
            <dd>{{ dato.valor }}</dd>
          </div>
        </dl>
        <span class="tarjeta__ver">Ver ficha</span>
      </div>
    </a>
  </article>
</template>

<style scoped>
.tarjeta {
  background: var(--ap-superficie);
  border: 1px solid var(--ap-borde);
  border-radius: 12px;
  overflow: hidden;
  transition:
    border-color 0.2s ease,
    box-shadow 0.2s ease;
}

.tarjeta:hover,
.tarjeta:focus-within {
  border-color: var(--q-secondary);
  box-shadow: 0 12px 24px -12px rgb(15 23 42 / 25%);
}

.tarjeta__enlace {
  display: flex;
  flex-direction: column;
  height: 100%;
  color: inherit;
  text-decoration: none;
}

.tarjeta__enlace:focus-visible {
  outline: 2px solid var(--q-primary);
  outline-offset: -2px;
}

.tarjeta__imagen {
  position: relative;
  display: grid;
  place-items: center;
  height: 168px;
  background: #1e293b;
  overflow: hidden;
}

.tarjeta__imagen img {
  opacity: 0.5;
  transition: transform 0.4s ease;
}

.tarjeta:hover .tarjeta__imagen img {
  transform: scale(1.12);
}

.tarjeta__existencia {
  position: absolute;
  top: 12px;
  left: 12px;
  padding: 2px 10px;
  border-radius: 999px;
  background: #e2e8f0;
  font-size: 12px;
  font-weight: 500;
  line-height: 20px;
  color: var(--q-secondary);
}

.tarjeta__existencia--si {
  background: #dcfce7;
  color: var(--q-positive);
}

.tarjeta__cuerpo {
  display: flex;
  flex: 1;
  flex-direction: column;
  gap: 6px;
  padding: 16px 20px 20px;
}

.tarjeta__nombre {
  margin: 0;
  font-family: var(--fuente-titulos);
  font-size: 22px;
  font-weight: 600;
  line-height: 26px;
}

.tarjeta__material {
  margin: 0;
  font-size: 13px;
  line-height: 18px;
  color: var(--ap-texto-secundario);
}

.tarjeta__medidas {
  display: flex;
  gap: 24px;
  margin: 8px 0 0;
}

.tarjeta__medidas dt {
  font-size: 12px;
  color: var(--ap-texto-secundario);
}

.tarjeta__medidas dd {
  margin: 0;
  font-family: var(--fuente-codigo);
  font-size: 14px;
  font-weight: 600;
}

.tarjeta__ver {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  margin-top: auto;
  padding-top: 16px;
  font-size: 14px;
  font-weight: 500;
  color: var(--q-primary);
}

.tarjeta__ver::after {
  content: '→';
  transition: transform 0.2s ease;
}

.tarjeta:hover .tarjeta__ver::after {
  transform: translateX(4px);
}

@media (prefers-reduced-motion: reduce) {
  .tarjeta__imagen img,
  .tarjeta__ver::after {
    transition: none;
  }
}
</style>
