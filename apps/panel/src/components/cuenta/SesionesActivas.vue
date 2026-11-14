<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { useQuasar } from 'quasar';
import type { SesionActiva } from '@aleacion-prima/types';
import { useApi } from '@/services/api';

const props = defineProps<{ usuarioId?: number }>();

const $q = useQuasar();
const sesiones = ref<SesionActiva[]>([]);
const cargando = ref(true);
const cerrando = ref(false);

const ruta = computed(() =>
  props.usuarioId !== undefined ? `/usuarios/${props.usuarioId}/sesiones` : '/sesiones',
);

const fecha = new Intl.DateTimeFormat('es-MX', { dateStyle: 'medium', timeStyle: 'short' });
const formatearFecha = (iso: string | null): string => (iso ? fecha.format(new Date(iso)) : '—');

const cargar = async (): Promise<void> => {
  cargando.value = true;
  const { data, error } = await useApi(ruta.value).json<SesionActiva[]>();
  if (error.value) {
    $q.notify({ type: 'negative', message: 'No se pudieron cargar las sesiones' });
  } else {
    sesiones.value = data.value ?? [];
  }
  cargando.value = false;
};

const cerrar = async (sesion: SesionActiva): Promise<void> => {
  cerrando.value = true;
  const { error } = await useApi(`${ruta.value}/${encodeURIComponent(sesion.id)}`)
    .delete()
    .text();
  cerrando.value = false;
  if (error.value) {
    $q.notify({ type: 'negative', message: 'No se pudo cerrar la sesión' });
    return;
  }
  $q.notify({ type: 'positive', message: 'Sesión cerrada' });
  await cargar();
};

const cerrarOtras = async (): Promise<void> => {
  cerrando.value = true;
  const { error } = await useApi(ruta.value).delete().text();
  cerrando.value = false;
  if (error.value) {
    $q.notify({ type: 'negative', message: 'No se pudieron cerrar las demás sesiones' });
    return;
  }
  $q.notify({ type: 'positive', message: 'Cerramos las demás sesiones' });
  await cargar();
};

onMounted(cargar);
</script>

<template>
  <div>
    <div class="row items-center justify-between q-mb-sm">
      <div class="text-body2 text-grey-7">
        Dispositivos y apps con sesión abierta en tu cuenta. Cierra las que no reconozcas.
      </div>
      <q-btn
        flat
        no-caps
        color="primary"
        label="Cerrar las demás"
        :disable="cargando || cerrando || sesiones.length < 2"
        @click="cerrarOtras"
      />
    </div>

    <div v-if="cargando" class="row justify-center q-pa-md">
      <q-spinner color="primary" size="32px" />
    </div>

    <q-list v-else-if="sesiones.length" bordered separator>
      <q-item v-for="sesion in sesiones" :key="sesion.id">
        <q-item-section avatar>
          <q-icon :name="sesion.tipo === 'app' ? 'smartphone' : 'computer'" color="grey-7" />
        </q-item-section>
        <q-item-section>
          <q-item-label>
            {{ sesion.dispositivo }}
            <q-chip
              v-if="sesion.actual"
              dense
              color="positive"
              text-color="white"
              label="Esta sesión"
              class="q-ml-sm"
            />
          </q-item-label>
          <q-item-label caption>
            {{ sesion.ip ?? '—' }} · {{ formatearFecha(sesion.ultima_actividad) }}
          </q-item-label>
        </q-item-section>
        <q-item-section side>
          <q-btn
            flat
            dense
            no-caps
            color="negative"
            label="Cerrar"
            :disable="sesion.actual || cerrando"
            @click="cerrar(sesion)"
          />
        </q-item-section>
      </q-item>
    </q-list>

    <div v-else class="text-grey-7 q-pa-md">No hay sesiones activas.</div>
  </div>
</template>
