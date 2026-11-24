<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import type { ProductoPublico } from '@aleacion-prima/types';
import { listarProductos, obtenerProducto } from '../../services/api';
import { datosTecnicos, existencia, material } from '../../composables/especificaciones';
import { useCatalogoEnVivo } from '../../composables/catalogoEnVivo';
import CuadriculaTarjetas from './CuadriculaTarjetas.vue';
import TarjetaProducto from './TarjetaProducto.vue';

const producto = ref<ProductoPublico | null>(null);
const otros = ref<ProductoPublico[]>([]);
const estado = ref<'cargando' | 'listo' | 'no-encontrado'>('cargando');
const cambio = ref(false);

const datos = computed(() => (producto.value ? datosTecnicos(producto.value) : []));

const cargar = async (): Promise<void> => {
  const id = Number(new URLSearchParams(window.location.search).get('id'));
  const [encontrado, lista] = await Promise.all([
    Number.isInteger(id) && id > 0 ? obtenerProducto(id) : Promise.resolve(null),
    listarProductos(),
  ]);
  producto.value = encontrado;
  otros.value = (lista ?? []).filter((otro) => otro.id !== id).slice(0, 3);
  estado.value = encontrado ? 'listo' : 'no-encontrado';
  if (encontrado) document.title = `${encontrado.nombre} · Aleación Prima`;
};

onMounted(cargar);

useCatalogoEnVivo({
  disponibilidad: (evento) => {
    if (!producto.value || evento.id !== producto.value.id) return;
    producto.value = {
      ...producto.value,
      disponible: evento.disponible,
      cantidad: evento.cantidad,
    };
    cambio.value = true;
    setTimeout(() => (cambio.value = false), 1600);
  },
  catalogo: () => void cargar(),
});
</script>

<template>
  <p v-if="estado === 'cargando'" class="aviso">Cargando producto…</p>

  <div v-else-if="estado === 'no-encontrado' || !producto" class="aviso">
    <p>Ese producto no está en el catálogo.</p>
    <a class="enlace" href="/catalogo">
      <img src="/iconos/chevron-left.svg" width="20" height="20" alt="" />
      Volver al catálogo
    </a>
  </div>

  <template v-else>
    <section class="banda banda-oscura">
      <a class="volver" href="/catalogo">
        <img src="/iconos/chevron-left.svg" width="20" height="20" alt="" />
        Catálogo
      </a>
      <h1>
        <span class="mascara">
          <span>{{ producto.nombre }}</span>
        </span>
      </h1>
      <div class="banda__datos">
        <span>{{ material(producto) }}</span>
        <span
          class="banda__existencia"
          :class="{ 'banda__existencia--si': producto.disponible, destello: cambio }"
        >
          {{ existencia(producto) }}
        </span>
      </div>
    </section>

    <section class="detalle">
      <div class="detalle__imagen">
        <img src="/logo.svg" width="72" height="72" alt="" />
      </div>

      <div class="detalle__ficha">
        <h2>Especificaciones</h2>
        <dl>
          <div v-for="(dato, i) in datos" :key="dato.etiqueta" :style="{ '--i': i }">
            <dt>{{ dato.etiqueta }}</dt>
            <dd :class="{ mono: dato.mono }">{{ dato.valor }}</dd>
          </div>
          <div :style="{ '--i': datos.length }">
            <dt>Disponibilidad</dt>
            <dd class="mono" :class="{ positivo: producto.disponible, destello: cambio }">
              {{ existencia(producto) }}
            </dd>
          </div>
        </dl>

        <div class="detalle__acciones">
          <a class="boton boton--primario boton--grande" href="/cotizar">Cotizar esta pieza</a>
          <a class="detalle__volver" href="/catalogo">Volver al catálogo</a>
        </div>
      </div>
    </section>

    <section v-if="otros.length" class="otras">
      <h2 class="otras__titulo">Otras piezas</h2>
      <CuadriculaTarjetas>
        <TarjetaProducto
          v-for="(otro, i) in otros"
          :key="otro.id"
          :producto="otro"
          :style="{ '--i': i }"
        />
      </CuadriculaTarjetas>
    </section>
  </template>
</template>

<style scoped>
.banda {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 12px;
  padding: 32px 32px 40px;
}

h1 {
  margin: 8px 0 0;
  font-size: clamp(36px, 5vw, 72px);
  font-weight: 700;
  line-height: 0.95;
  text-transform: uppercase;
}

.banda__datos {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 12px 16px;
  font-size: 16px;
  line-height: 24px;
  color: #cbd5e1;
}

.banda__existencia {
  padding: 2px 12px;
  border-radius: 999px;
  background: #334155;
  font-size: 13px;
  font-weight: 500;
  color: #e2e8f0;
}

.banda__existencia--si {
  background: #dcfce7;
  color: var(--q-positive);
}

.detalle {
  display: grid;
  grid-template-columns: minmax(0, 1.15fr) minmax(0, 1fr);
  gap: 48px;
  padding: 48px 32px 72px;
}

.detalle__imagen {
  display: grid;
  place-items: center;
  min-height: 360px;
  background: #1e293b;
  border-radius: 12px;
}

.detalle__imagen img {
  opacity: 0.5;
}

.detalle__ficha {
  display: flex;
  flex-direction: column;
}

.detalle__ficha h2 {
  padding-bottom: 14px;
  border-bottom: 1px solid var(--q-dark);
  font-size: 28px;
  font-weight: 700;
  line-height: 32px;
  text-transform: uppercase;
}

dl {
  margin: 0;
}

dl div {
  display: grid;
  grid-template-columns: 140px minmax(0, 1fr);
  gap: 16px;
  padding: 14px 0;
  border-bottom: 1px solid #cbd5e1;
}

dt {
  font-size: 14px;
  color: var(--ap-texto-secundario);
}

dd {
  margin: 0;
  font-size: 15px;
  font-weight: 600;
}

dd.mono {
  font-family: var(--fuente-codigo);
  font-size: 14px;
}

dd.positivo {
  color: var(--q-positive);
}

.destello {
  animation: destello 1.6s ease-out;
}

.detalle__acciones {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 24px;
  margin-top: 32px;
}

.detalle__volver {
  font-size: 15px;
  font-weight: 500;
  color: var(--q-dark);
  text-decoration: underline;
  text-decoration-color: #cbd5e1;
  text-underline-offset: 5px;
}

.detalle__volver:hover {
  text-decoration-color: var(--q-primary);
}

.detalle__volver:focus-visible {
  outline: 2px solid var(--q-accent);
  outline-offset: 3px;
}

.otras {
  padding: 0 32px 40px;
}

.otras__titulo {
  margin: 0 0 24px;
  padding-left: 14px;
  border-left: 4px solid var(--q-primary);
  font-size: 28px;
  font-weight: 700;
  line-height: 32px;
  text-transform: uppercase;
}

@media (prefers-reduced-motion: no-preference) {
  .volver,
  .banda__datos {
    animation: entrar 0.6s cubic-bezier(0.2, 0.7, 0.2, 1) 0.35s backwards;
  }

  .detalle__imagen {
    animation: entrar 0.7s cubic-bezier(0.2, 0.7, 0.2, 1) 0.2s backwards;
  }

  dl div {
    animation: entrar 0.5s cubic-bezier(0.2, 0.7, 0.2, 1) backwards;
    animation-delay: calc(0.4s + var(--i) * 80ms);
  }
}

@keyframes destello {
  0% {
    background: rgb(249 115 22 / 35%);
  }

  100% {
    background: transparent;
  }
}

@media (max-width: 1023px) {
  .detalle {
    grid-template-columns: 1fr;
    gap: 32px;
  }
}

@media (max-width: 599px) {
  .banda {
    padding: 24px 16px 32px;
  }

  .detalle {
    padding: 32px 16px 56px;
  }

  .otras {
    padding: 0 16px 40px;
  }

  dl div {
    grid-template-columns: 112px minmax(0, 1fr);
  }
}
</style>
