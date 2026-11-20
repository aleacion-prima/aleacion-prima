<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import type { ProductoPublico } from '@aleacion-prima/types';
import { listarProductos, obtenerProducto } from '../../services/api';
import { datosTecnicos } from '../../composables/especificaciones';
import { useCatalogoEnVivo } from '../../composables/catalogoEnVivo';
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
    producto.value = { ...producto.value, disponible: evento.disponible };
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
    <section class="detalle">
      <div class="detalle__imagen">
        <img src="/logo.svg" width="28" height="28" alt="" />
      </div>

      <div class="detalle__ficha">
        <h1 class="detalle__nombre">{{ producto.nombre }}</h1>

        <div class="ficha">
          <h2 class="ficha__titulo">Especificaciones técnicas</h2>
          <div v-for="dato in datos" :key="dato.etiqueta" class="ficha__fila">
            <span class="ficha__etiqueta">{{ dato.etiqueta }}</span>
            <span :class="dato.mono ? 'ficha__valor ficha__valor--mono' : 'ficha__valor'">
              {{ dato.valor }}
            </span>
          </div>
          <div class="ficha__fila">
            <span class="ficha__etiqueta">Disponibles</span>
            <span
              class="ficha__valor ficha__valor--mono"
              :class="{
                'ficha__valor--positivo': producto.disponible > 0,
                'ficha__valor--cambio': cambio,
              }"
            >
              {{ producto.disponible }}
            </span>
          </div>
        </div>

        <a class="boton boton--contorno boton--alto" href="/catalogo">
          <img src="/iconos/arrow-back.svg" width="20" height="20" alt="" />
          Volver al catálogo
        </a>
      </div>
    </section>

    <section v-if="otros.length" class="otras">
      <h2 class="otras__titulo">Otras piezas</h2>
      <div class="otras__lista">
        <TarjetaProducto v-for="otro in otros" :key="otro.id" :producto="otro" />
      </div>
    </section>
  </template>
</template>

<style scoped>
.detalle {
  display: flex;
  gap: 57px;
  padding: 40px 47px;
}

.detalle__imagen {
  flex: 1 1 0;
  display: flex;
  align-items: center;
  justify-content: center;
  height: 288px;
  background: var(--ap-borde);
  border-radius: 12px;
}

.detalle__imagen img {
  width: 56px;
  height: 56px;
  opacity: 0.35;
  filter: grayscale(1);
}

.detalle__ficha {
  display: flex;
  flex-direction: column;
  gap: 24px;
  width: 523px;
}

.detalle__nombre {
  margin: 0;
  font-family: var(--fuente-titulos);
  font-size: 32px;
  font-weight: 600;
  line-height: 36px;
  color: var(--q-dark);
}

.ficha {
  padding: 20px;
  background: var(--ap-superficie);
  border: 1px solid var(--ap-borde);
  border-radius: 12px;
}

.ficha__titulo {
  margin: 0 0 8px;
  font-family: var(--fuente-titulos);
  font-size: 20px;
  font-weight: 600;
  line-height: 24px;
  color: var(--q-dark);
}

.ficha__fila {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  padding: 8px 0;
  font-size: 14px;
  line-height: 20px;
}

.ficha__fila + .ficha__fila {
  border-top: 0.8px solid var(--ap-borde);
}

.ficha__etiqueta {
  color: var(--ap-texto-secundario);
}

.ficha__valor {
  font-weight: 600;
  color: var(--q-dark);
  text-align: right;
}

.ficha__valor--mono {
  font-family: var(--fuente-codigo);
  font-size: 13px;
}

.ficha__valor--positivo {
  color: var(--q-positive);
}

.ficha__valor--cambio {
  animation: destello 1.6s ease-out;
}

.otras {
  display: flex;
  flex-direction: column;
  gap: 24px;
  padding: 0 32px 40px;
}

.otras__titulo {
  margin: 0;
  font-family: var(--fuente-titulos);
  font-size: 28px;
  font-weight: 600;
  line-height: 32px;
  text-align: center;
  color: var(--q-dark);
}

.otras__lista {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 37px;
}

@keyframes destello {
  0% {
    background: rgb(249 115 22 / 30%);
  }

  100% {
    background: transparent;
  }
}

@media (max-width: 1023px) {
  .detalle {
    flex-direction: column;
    gap: 24px;
    padding: 24px;
  }

  .detalle__ficha {
    width: auto;
  }

  .otras__lista {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 24px;
  }
}

@media (max-width: 599px) {
  .otras__lista {
    grid-template-columns: 1fr;
  }
}
</style>
