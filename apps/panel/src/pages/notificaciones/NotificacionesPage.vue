<script setup lang="ts">
import { onMounted, ref, watch } from 'vue';
import { useQuasar } from 'quasar';
import { useRouter } from 'vue-router';
import type { Notificacion } from '@aleacion-prima/types';
import PaginacionTabla from '@/components/comunes/PaginacionTabla.vue';
import { useApi } from '@/services/api';
import { useNotificaciones } from '@/composables/notificaciones';

interface RespuestaPaginada {
  data: Notificacion[];
  meta: { current_page: number; per_page: number; total: number };
}

type FiltroTipo = 'todos' | Notificacion['tipo'];
type FiltroLectura = 'todas' | 'no_leidas' | 'leidas';

const $q = useQuasar();
const router = useRouter();
const { pendientes, actualizar } = useNotificaciones();
const filas = ref<Notificacion[]>([]);
const cargando = ref(true);
const tipo = ref<FiltroTipo>('todos');
const lectura = ref<FiltroLectura>('todas');
const pagina = ref(1);
const porPagina = ref(15);
const total = ref(0);

const etiquetaTipo: Record<FiltroTipo, string> = {
  todos: 'Todos los tipos',
  imprevisto: 'Imprevistos',
  stock_minimo: 'Stock mínimo',
  lote_por_confirmar: 'Por confirmar',
};

const cargar = async (): Promise<void> => {
  cargando.value = true;
  const parametros = new URLSearchParams({ page: String(pagina.value) });
  if (tipo.value !== 'todos') parametros.set('tipo', tipo.value);
  if (lectura.value === 'no_leidas') parametros.set('no_leidas', '1');
  if (lectura.value === 'leidas') parametros.set('leidas', '1');

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
  if (notificacion.leida) return;
  const respuesta = await useApi(`/notificaciones/${notificacion.id}/leida`).patch().text();
  if (respuesta.error.value) {
    $q.notify({ type: 'negative', message: 'No se pudo marcar la notificación' });
    return;
  }
  notificacion.leida = true;
  actualizar(Math.max(0, pendientes.value - 1));
};

const marcarTodas = async (): Promise<void> => {
  const respuesta = await useApi('/notificaciones/leidas').post().text();
  if (respuesta.error.value) {
    $q.notify({ type: 'negative', message: 'No se pudieron marcar las notificaciones' });
    return;
  }
  filas.value.forEach((notificacion) => {
    notificacion.leida = true;
  });
  actualizar(0);
};

const abrir = async (notificacion: Notificacion): Promise<void> => {
  await marcar(notificacion);
  if (notificacion.codigo) {
    await router.push(
      notificacion.tipo === 'stock_minimo'
        ? '/inventario/insumos'
        : `/lotes/${encodeURIComponent(notificacion.codigo)}`,
    );
  }
};

watch([tipo, lectura], () => {
  pagina.value = 1;
  void cargar();
});
watch(pagina, () => void cargar());
onMounted(() => void cargar());
</script>

<template>
  <q-page padding>
    <div class="row items-start justify-between q-mb-lg">
      <div>
        <div class="text-h4">Notificaciones</div>
        <div class="text-secundario q-mt-xs">Avisos importantes de la operación.</div>
      </div>
      <q-btn
        flat
        no-caps
        label="Marcar todas como leídas"
        :disable="pendientes === 0"
        @click="marcarTodas"
      />
    </div>

    <div class="row q-col-gutter-md q-mb-md">
      <q-select
        v-model="tipo"
        outlined
        dense
        emit-value
        map-options
        :options="Object.entries(etiquetaTipo).map(([value, label]) => ({ value, label }))"
        label="Tipo"
        class="col-12 col-sm-4"
      />
      <q-select
        v-model="lectura"
        outlined
        dense
        emit-value
        map-options
        :options="[
          { value: 'todas', label: 'Todas' },
          { value: 'no_leidas', label: 'No leídas' },
          { value: 'leidas', label: 'Leídas' },
        ]"
        label="Estado"
        class="col-12 col-sm-4"
      />
    </div>

    <q-card flat bordered>
      <q-list separator>
        <q-item
          v-for="notificacion in filas"
          :key="notificacion.id"
          clickable
          @click="abrir(notificacion)"
        >
          <q-item-section avatar
            ><q-icon
              :name="notificacion.leida ? 'notifications_none' : 'notifications_active'"
              :color="notificacion.leida ? 'grey' : 'primary'"
          /></q-item-section>
          <q-item-section>
            <q-item-label :class="{ 'text-weight-medium': !notificacion.leida }">{{
              notificacion.mensaje
            }}</q-item-label>
            <q-item-label caption>{{ notificacion.fecha }}</q-item-label>
          </q-item-section>
          <q-item-section side
            ><q-badge v-if="!notificacion.leida" color="accent" text-color="dark"
              >Nueva</q-badge
            ></q-item-section
          >
        </q-item>
        <q-item v-if="!cargando && !filas.length"
          ><q-item-section class="text-center text-secundario"
            >No hay notificaciones.</q-item-section
          ></q-item
        >
      </q-list>
      <div class="q-pa-md">
        <PaginacionTabla
          v-model:pagina="pagina"
          :por-pagina="porPagina"
          :total="total"
          sustantivo="notificaciones"
        />
      </div>
    </q-card>
  </q-page>
</template>
