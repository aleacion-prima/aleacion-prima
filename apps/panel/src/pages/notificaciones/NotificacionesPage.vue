<script setup lang="ts">
import { onMounted, ref, watch } from 'vue';
import { useQuasar } from 'quasar';
import { useRouter } from 'vue-router';
import type { Notificacion } from '@aleacion-prima/types';
import EstadoVacio from '@/components/comunes/EstadoVacio.vue';
import PaginacionTabla from '@/components/comunes/PaginacionTabla.vue';
import { useApi } from '@/services/api';
import { useNotificaciones } from '@/composables/notificaciones';
import {
  ESTILOS_NOTIFICACION,
  accionDeNotificacion,
  detalleDeNotificacion,
  fechaDeNotificacion,
  rutaDeNotificacion,
  tituloDeNotificacion,
} from '@/catalogos/notificaciones';

interface RespuestaPaginada {
  data: Notificacion[];
  meta: { current_page: number; per_page: number; total: number };
}

type FiltroTipo = 'todos' | Notificacion['tipo'];

const FILTROS_TIPO: { value: FiltroTipo; etiqueta: string }[] = [
  { value: 'todos', etiqueta: 'Todas' },
  { value: 'imprevisto', etiqueta: 'Imprevistos' },
  { value: 'stock_minimo', etiqueta: 'Stock mínimo' },
  { value: 'lote_por_confirmar', etiqueta: 'Lotes por confirmar' },
];

const $q = useQuasar();
const router = useRouter();
const { pendientes, actualizar, marcarLeida } = useNotificaciones();
const filas = ref<Notificacion[]>([]);
const cargando = ref(true);
const tipo = ref<FiltroTipo>('todos');
const pagina = ref(1);
const porPagina = ref(15);
const total = ref(0);

const cargar = async (): Promise<void> => {
  cargando.value = true;
  const parametros = new URLSearchParams({ page: String(pagina.value) });
  if (tipo.value !== 'todos') parametros.set('tipo', tipo.value);

  const respuesta = await useApi<RespuestaPaginada>(
    `/notificaciones?${parametros}`,
  ).json<RespuestaPaginada>();
  cargando.value = false;
  if (respuesta.error.value) {
    $q.notify({ type: 'negative', message: 'No se pudieron cargar las notificaciones' });
    return;
  }
  filas.value = respuesta.data.value?.data ?? [];
  total.value = respuesta.data.value?.meta.total ?? 0;
  porPagina.value = respuesta.data.value?.meta.per_page ?? 15;
};

const marcar = async (notificacion: Notificacion): Promise<void> => {
  if (!(await marcarLeida(notificacion))) {
    $q.notify({ type: 'negative', message: 'No se pudo marcar la notificación' });
  }
};

const marcarTodas = async (): Promise<void> => {
  const respuesta = await useApi('/notificaciones/leidas').post().text();
  if (respuesta.error.value) {
    $q.notify({ type: 'negative', message: 'No se pudieron marcar las notificaciones' });
    return;
  }
  actualizar(0);
  // Se recarga para que el filtro y la paginación reflejen lo que quedó en la API.
  pagina.value = 1;
  await cargar();
};

const abrir = async (notificacion: Notificacion): Promise<void> => {
  await marcar(notificacion);
  const ruta = rutaDeNotificacion(notificacion);
  if (ruta) await router.push(ruta);
};

watch(tipo, () => {
  pagina.value = 1;
  void cargar();
});
watch(pagina, () => void cargar());
onMounted(() => void cargar());
</script>

<template>
  <q-page padding>
    <div class="row items-start justify-between q-mb-lg q-col-gutter-y-sm">
      <div>
        <div class="text-h4">Centro de notificaciones</div>
        <div class="text-secundario q-mt-xs">Imprevistos, stock mínimo y lotes por confirmar</div>
      </div>
      <q-btn
        outline
        no-caps
        color="secondary"
        icon="check_circle_outline"
        label="Marcar todas como leídas"
        class="boton-marcar"
        :disable="pendientes === 0"
        @click="marcarTodas"
      />
    </div>

    <q-tabs
      v-model="tipo"
      no-caps
      align="left"
      active-color="primary"
      indicator-color="primary"
      class="tabs-tipo q-mb-md"
    >
      <q-tab
        v-for="filtro in FILTROS_TIPO"
        :key="filtro.value"
        :name="filtro.value"
        :label="filtro.etiqueta"
      />
    </q-tabs>

    <div v-if="cargando && !filas.length" class="q-pa-xl flex flex-center">
      <q-spinner color="primary" size="40px" />
    </div>

    <EstadoVacio
      v-else-if="!filas.length"
      icono="notifications_off"
      titulo="No hay notificaciones"
      descripcion="No hay avisos que coincidan con estos filtros."
    />

    <template v-else>
      <q-card
        v-for="notificacion in filas"
        :key="notificacion.id"
        flat
        bordered
        class="notificacion q-mb-md cursor-pointer"
        :class="{ 'notificacion--leida': notificacion.leida }"
        role="button"
        tabindex="0"
        @click="abrir(notificacion)"
        @keyup.enter="abrir(notificacion)"
      >
        <q-card-section>
          <div class="row items-start justify-between no-wrap q-gutter-md">
            <div class="text-caption text-secundario">
              {{ fechaDeNotificacion(notificacion.fecha) }}
            </div>
            <q-badge
              :color="ESTILOS_NOTIFICACION[notificacion.tipo].color"
              text-color="white"
              class="badge-tipo"
            >
              {{ ESTILOS_NOTIFICACION[notificacion.tipo].etiqueta }}
            </q-badge>
          </div>
          <div class="notificacion__titulo q-mt-sm">
            {{ tituloDeNotificacion(notificacion) }}
          </div>
          <div
            v-if="detalleDeNotificacion(notificacion)"
            class="text-body2 text-secundario q-mt-xs"
          >
            {{ detalleDeNotificacion(notificacion) }}
          </div>
          <div class="row justify-end q-mt-md">
            <q-btn
              outline
              no-caps
              color="secondary"
              :label="accionDeNotificacion(notificacion)"
              class="boton-ver"
              @click.stop="abrir(notificacion)"
            />
          </div>
        </q-card-section>
      </q-card>

      <PaginacionTabla
        v-if="filas.length"
        v-model:pagina="pagina"
        :por-pagina="porPagina"
        :total="total"
        sustantivo="notificaciones"
        class="q-mt-md"
      />
    </template>
  </q-page>
</template>

<style scoped lang="scss">
.tabs-tipo {
  :deep(.q-tab) {
    min-height: 40px;
    padding: 0 12px;
    text-transform: lowercase;
  }

  :deep(.q-tab__label) {
    font-size: 13px;
    font-weight: 500;
    letter-spacing: 0.01em;
  }

  :deep(.q-tab__indicator) {
    height: 2px;
  }
}

.notificacion {
  background: white;
  border-radius: 12px;
  border-color: $acero-claro;
  transition:
    border-color $duracion-rapida ease,
    box-shadow $duracion-rapida ease;

  &:hover {
    border-color: $grafito-medio;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.06);
  }

  &--leida {
    opacity: 0.7;
  }
}

.notificacion__titulo {
  font-family: $fuente-titulos;
  font-size: 18px;
  font-weight: 700;
  line-height: 1.25;
  color: $dark;
}

.badge-tipo {
  border-radius: 999px;
  padding: 4px 10px;
  font-size: 12px;
  font-weight: 500;
  line-height: 1.4;
}

.boton-marcar,
.boton-ver {
  border-radius: 8px;
}
</style>
