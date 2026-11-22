<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { useQuasar } from 'quasar';
import { useRouter } from 'vue-router';
import type { Notificacion, Rol } from '@aleacion-prima/types';
import { ETIQUETAS_ROL } from '@/catalogos/roles';
import {
  ESTILOS_NOTIFICACION,
  fechaDeNotificacion,
  rutaDeNotificacion,
  tituloDeNotificacion,
} from '@/catalogos/notificaciones';
import { useNotificaciones } from '@/composables/notificaciones';
import LogoMarca from './LogoMarca.vue';

const props = withDefaults(defineProps<{ nombre: string; rol: Rol; pendientes?: number }>(), {
  pendientes: 0,
});

const emit = defineEmits<{ 'alternar-menu': []; salir: [] }>();

const $q = useQuasar();
const router = useRouter();
const { cargarRecientes, marcarLeida } = useNotificaciones();
const busqueda = ref('');
const recientes = ref<Notificacion[]>([]);
const cargandoVistazo = ref(false);
const rolEtiqueta = computed(() => ETIQUETAS_ROL[props.rol]);

const cargarVistazo = async (): Promise<void> => {
  cargandoVistazo.value = true;
  recientes.value = await cargarRecientes();
  cargandoVistazo.value = false;
};

const abrirNotificacion = async (notificacion: Notificacion): Promise<void> => {
  if (!(await marcarLeida(notificacion))) {
    $q.notify({ type: 'negative', message: 'No se pudo marcar la notificación' });
  }

  const ruta = rutaDeNotificacion(notificacion);
  if (ruta) await router.push(ruta);
};

watch(
  () => props.pendientes,
  () => void cargarVistazo(),
);
</script>

<template>
  <q-header class="bg-secondary">
    <q-toolbar class="q-px-md" style="min-height: 64px">
      <q-btn flat dense round icon="menu" aria-label="Menú" @click="emit('alternar-menu')" />
      <q-toolbar-title shrink class="row items-center no-wrap q-gutter-x-sm">
        <LogoMarca />
        <span class="text-h6 text-uppercase marca">Aleación Prima</span>
      </q-toolbar-title>
      <q-space />
      <q-input
        v-model="busqueda"
        dense
        dark
        standout="bg-grey-9"
        placeholder="Buscar lote, producto o insumo…"
        class="gt-sm"
        style="width: 560px"
      >
        <template #prepend><q-icon name="search" /></template>
      </q-input>
      <q-space />
      <q-btn flat round icon="notifications" aria-label="Notificaciones">
        <q-badge v-if="pendientes" color="accent" text-color="white" floating rounded>
          {{ pendientes }}
        </q-badge>
        <q-menu
          anchor="bottom right"
          self="top right"
          :offset="[0, 8]"
          @before-show="cargarVistazo"
        >
          <div class="vistazo">
            <div class="text-subtitle1 text-weight-medium q-px-md q-pt-md">Notificaciones</div>
            <div v-if="cargandoVistazo" class="flex flex-center q-pa-lg">
              <q-spinner color="primary" size="24px" />
            </div>
            <q-list v-else-if="recientes.length" dense class="q-py-xs">
              <q-item
                v-for="notificacion in recientes"
                :key="notificacion.id"
                v-close-popup
                clickable
                class="vistazo__item"
                @click="abrirNotificacion(notificacion)"
              >
                <q-item-section>
                  <div class="row items-center q-gutter-xs no-wrap">
                    <q-badge
                      :color="ESTILOS_NOTIFICACION[notificacion.tipo].color"
                      text-color="white"
                      class="badge-tipo"
                    >
                      {{ ESTILOS_NOTIFICACION[notificacion.tipo].etiqueta }}
                    </q-badge>
                    <span class="text-caption text-secundario ellipsis">
                      {{ fechaDeNotificacion(notificacion.fecha) }}
                    </span>
                  </div>
                  <div class="vistazo__titulo ellipsis">
                    {{ tituloDeNotificacion(notificacion) }}
                  </div>
                </q-item-section>
              </q-item>
            </q-list>
            <div v-else class="column items-center q-pa-lg text-secundario">
              <q-icon name="notifications_none" size="32px" />
              <div class="q-mt-sm">Revisa el centro de notificaciones</div>
            </div>
            <div class="flex justify-center q-pa-sm">
              <q-btn
                v-close-popup
                flat
                no-caps
                color="primary"
                label="Ver todas"
                to="/notificaciones"
              />
            </div>
          </div>
        </q-menu>
      </q-btn>
      <div class="column items-end q-mx-sm">
        <span class="text-weight-medium">{{ nombre }}</span>
        <span class="text-caption">{{ rolEtiqueta }}</span>
      </div>
      <q-btn flat round icon="account_circle" aria-label="Cuenta">
        <q-menu>
          <q-list>
            <q-item v-close-popup clickable to="/mi-cuenta">
              <q-item-section avatar><q-icon name="manage_accounts" /></q-item-section>
              <q-item-section>Mi cuenta</q-item-section>
            </q-item>
            <q-separator />
            <q-item v-close-popup clickable @click="emit('salir')">
              <q-item-section avatar><q-icon name="logout" /></q-item-section>
              <q-item-section>Cerrar sesión</q-item-section>
            </q-item>
          </q-list>
        </q-menu>
      </q-btn>
    </q-toolbar>
  </q-header>
</template>

<style scoped lang="scss">
.marca {
  letter-spacing: 0.08em;
}

.vistazo {
  width: 320px;
}

.vistazo__item {
  min-height: 56px;
  padding: 8px 16px;
}

.vistazo__titulo {
  max-width: 272px;
  margin-top: 2px;
  font-size: 13px;
  font-weight: 500;
  color: $dark;
}

.badge-tipo {
  border-radius: 999px;
  padding: 2px 8px;
  font-size: 11px;
  font-weight: 500;
  line-height: 1.4;
}
</style>
