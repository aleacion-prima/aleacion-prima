<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useQuasar } from 'quasar';
import type { Area, CrearLote, Insumo, Lote, Producto } from '@aleacion-prima/types';
import { useApi } from '@/services/api';
import { CAPACIDAD_COLADA_KG } from '@/catalogos/planta';
import EditorRecorrido from '@/components/lotes/EditorRecorrido.vue';
import AvisoSistema from '@/components/comunes/AvisoSistema.vue';
import NumeroAnimado from '@/components/comunes/NumeroAnimado.vue';

interface ErrorApi {
  message?: string;
  errors?: Record<string, string[]>;
}

const router = useRouter();
const $q = useQuasar();

const productos = ref<Producto[]>([]);
const areas = ref<Area[]>([]);
const insumos = ref<Insumo[]>([]);
const cargando = ref(true);
const guardando = ref(false);
const errores = ref<Record<string, string[]>>({});

const productoId = ref<number | null>(null);
const cantidad = ref<number | null>(null);
const recorrido = ref<number[]>([]);

const producto = computed(() => productos.value.find((actual) => actual.id === productoId.value));

const opcionesProducto = computed(() =>
  productos.value.map((actual) => ({
    value: actual.id,
    label: `${actual.codigo} · ${actual.nombre}`,
  })),
);

const piezas = computed(() => Math.max(cantidad.value ?? 0, 0));

const existenciaDe = (insumoId: number): number =>
  Number(insumos.value.find((insumo) => insumo.id === insumoId)?.existencia ?? 0);

const asignacion = computed(() =>
  (producto.value?.receta ?? []).map((renglon) => {
    const necesario = Number(renglon.cantidad_por_pieza) * piezas.value;
    const existencia = existenciaDe(renglon.insumo_id);
    return { ...renglon, necesario, existencia, falta: necesario > existencia };
  }),
);

const metal = computed(() =>
  asignacion.value
    .filter((renglon) => renglon.categoria === 'metal')
    .reduce((total, renglon) => total + renglon.necesario, 0),
);

const excedeColada = computed(() => metal.value > CAPACIDAD_COLADA_KG);

const colorMetal = computed(() => {
  const uso = metal.value / CAPACIDAD_COLADA_KG;
  if (uso > 1) return 'negative';
  if (uso >= 0.85) return 'warning';
  return 'accent';
});

const sacudidas = ref(0);
watch(excedeColada, (excede) => {
  if (excede) sacudidas.value++;
});

const restablecer = (): void => {
  recorrido.value = producto.value?.recorrido.map((parada) => parada.id) ?? [];
};

watch(productoId, restablecer);

const erroresDe = (...campos: string[]): string[] =>
  campos.flatMap((campo) => errores.value[campo] ?? []);

const cargar = async (): Promise<void> => {
  const [respuestaProductos, respuestaAreas, respuestaInsumos] = await Promise.all([
    useApi<Producto[]>('/productos').json<Producto[]>(),
    useApi<Area[]>('/areas?activo=1').json<Area[]>(),
    useApi<Insumo[]>('/insumos').json<Insumo[]>(),
  ]);
  cargando.value = false;

  if (
    respuestaProductos.error.value ||
    respuestaAreas.error.value ||
    respuestaInsumos.error.value
  ) {
    $q.notify({ type: 'negative', message: 'No se pudieron cargar los catálogos' });
    return;
  }

  productos.value = (respuestaProductos.data.value ?? []).filter((actual) => actual.activo);
  areas.value = respuestaAreas.data.value ?? [];
  insumos.value = respuestaInsumos.data.value ?? [];
};

const crear = async (): Promise<void> => {
  if (productoId.value === null || cantidad.value === null) return;

  guardando.value = true;
  errores.value = {};
  const cuerpo: CrearLote = {
    producto_id: productoId.value,
    cantidad_objetivo: cantidad.value,
    recorrido: recorrido.value,
  };
  const { data, error } = await useApi('/lotes').post(cuerpo).json<Lote | ErrorApi>();
  guardando.value = false;

  if (error.value) {
    errores.value = (data.value as ErrorApi | null)?.errors ?? {};
    if (!Object.keys(errores.value).length) {
      $q.notify({ type: 'negative', message: 'No se pudo crear el lote' });
    }
    return;
  }

  const lote = data.value as Lote;
  $q.notify({ type: 'positive', message: `Lote ${lote.codigo} creado` });
  void router.push(`/lotes/${lote.codigo}`);
};

onMounted(cargar);
</script>

<template>
  <q-page padding>
    <q-btn
      flat
      no-caps
      color="primary"
      icon="arrow_back"
      label="Volver a lotes"
      to="/lotes"
      class="q-mb-sm"
    />

    <div class="row items-center justify-between q-mb-md">
      <h1 class="text-h4 q-my-none">Nuevo lote</h1>
      <div class="row q-gutter-sm">
        <q-btn flat no-caps color="primary" label="Cancelar" to="/lotes" />
        <q-btn
          unelevated
          color="primary"
          icon="add"
          label="Crear lote"
          :loading="guardando"
          :disable="!producto || !cantidad || excedeColada"
          @click="crear"
        />
      </div>
    </div>

    <AvisoSistema
      v-for="mensaje in erroresDe('recorrido', 'cantidad_objetivo')"
      :key="mensaje"
      tipo="error"
      :mensaje="mensaje"
      class="q-mb-md"
    />

    <q-card flat bordered class="q-mb-md">
      <q-card-section>
        <h2 class="text-h6 q-mt-none q-mb-md">Datos del lote</h2>
        <div class="row q-col-gutter-md">
          <div class="col-12 col-md-8">
            <q-select
              v-model="productoId"
              :options="opcionesProducto"
              :loading="cargando"
              label="Producto"
              emit-value
              map-options
              outlined
              :error="erroresDe('producto_id').length > 0"
              :error-message="erroresDe('producto_id')[0]"
            />
          </div>
          <div class="col-12 col-md-4">
            <q-input
              v-model.number="cantidad"
              type="number"
              min="1"
              label="Cantidad objetivo"
              suffix="pzas"
              outlined
              :error="erroresDe('cantidad_objetivo').length > 0"
              :error-message="erroresDe('cantidad_objetivo')[0]"
            />
          </div>
        </div>
        <div v-if="producto" class="q-mt-sm">
          <div
            :key="sacudidas"
            class="text-body2 q-mb-xs metal"
            :class="{ 'text-negative': excedeColada, sacudir: sacudidas > 0 && excedeColada }"
          >
            Metal: <NumeroAnimado :valor="metal" :decimales="1" /> kg de
            {{ CAPACIDAD_COLADA_KG }} kg
          </div>
          <q-linear-progress
            :value="Math.min(metal / CAPACIDAD_COLADA_KG, 1)"
            :color="colorMetal"
            track-color="grey-3"
            rounded
            size="6px"
            :animation-speed="500"
            class="metal__barra"
          />
        </div>
      </q-card-section>
    </q-card>

    <div v-if="producto" class="entrada-escalonada">
      <q-card flat bordered class="q-mb-md">
        <q-card-section>
          <div class="row items-center justify-between">
            <h2 class="text-h6 q-my-none">Recorrido</h2>
            <q-btn
              flat
              no-caps
              color="primary"
              label="Restablecer al del producto"
              @click="restablecer"
            />
          </div>
          <EditorRecorrido v-model="recorrido" :areas="areas" />
        </q-card-section>
      </q-card>

      <div class="row q-col-gutter-md">
        <div class="col-12 col-md-8">
          <q-card flat bordered>
            <q-card-section>
              <h2 class="text-h6 q-mt-none q-mb-md">Insumos que se asignarán</h2>
              <q-markup-table flat separator="horizontal">
                <thead>
                  <tr>
                    <th class="text-left">Código</th>
                    <th class="text-left">Insumo</th>
                    <th class="text-right">Cantidad</th>
                    <th class="text-right">Existencia</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="renglon in asignacion"
                    :key="renglon.insumo_id"
                    class="renglon"
                    :class="{ 'renglon--falta': renglon.falta }"
                  >
                    <td class="codigo">{{ renglon.codigo }}</td>
                    <td>{{ renglon.nombre }}</td>
                    <td
                      class="text-right codigo"
                      :class="{ 'text-negative text-weight-bold': renglon.falta }"
                    >
                      <NumeroAnimado :valor="renglon.necesario" /> {{ renglon.unidad }}
                    </td>
                    <td class="text-right codigo">
                      <NumeroAnimado :valor="renglon.existencia" /> {{ renglon.unidad }}
                    </td>
                  </tr>
                </tbody>
              </q-markup-table>
            </q-card-section>
          </q-card>
        </div>

        <div class="col-12 col-md-4">
          <q-card flat bordered>
            <q-card-section>
              <h2 class="text-h6 q-mt-none q-mb-md">Resumen</h2>
              <q-list dense>
                <q-item>
                  <q-item-section class="text-secundario">Producto</q-item-section>
                  <q-item-section side class="text-dark">{{ producto.nombre }}</q-item-section>
                </q-item>
                <q-item>
                  <q-item-section class="text-secundario">Piezas</q-item-section>
                  <q-item-section side class="text-dark">
                    <NumeroAnimado :valor="piezas" formato="entero" />
                  </q-item-section>
                </q-item>
                <q-item>
                  <q-item-section class="text-secundario">Metal</q-item-section>
                  <q-item-section side :class="excedeColada ? 'text-negative' : 'text-dark'">
                    <span
                      ><NumeroAnimado :valor="metal" :decimales="1" /> kg de
                      {{ CAPACIDAD_COLADA_KG }} kg</span
                    >
                  </q-item-section>
                </q-item>
                <q-item>
                  <q-item-section class="text-secundario">Paradas</q-item-section>
                  <q-item-section side class="text-dark">{{ recorrido.length }}</q-item-section>
                </q-item>
              </q-list>
            </q-card-section>
          </q-card>
        </div>
      </div>
    </div>
  </q-page>
</template>

<style scoped lang="scss">
.metal {
  display: inline-block;
}

.metal__barra :deep(.q-linear-progress__model) {
  transition:
    transform 0.5s $curva-salida,
    background-color $duracion-media ease;
}

.renglon td {
  transition:
    background-color $duracion-media ease,
    color $duracion-media ease;
}

.renglon--falta td {
  background-color: rgba($negative, 0.06);
}
</style>
