<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import { useIntervalFn, useNow } from '@vueuse/core';
import type { Lote, LoteParada } from '@aleacion-prima/types';
import { useApi } from '@/services/api';
import { useSesionStore } from '@/stores/sesion';
import { iconoArea } from '@/catalogos/areas';
import { lotesEnArea, lotesPorLlegar, movimientosDeHoy } from '@/composables/miArea';
import { actualizarEnLista, useCanalesDeLotes, useLotesEnVivo } from '@/composables/lotesEnVivo';
import ChipEstado from '@/components/lotes/ChipEstado.vue';
import DialogoCaptura from '@/components/lotes/DialogoCaptura.vue';
import EstadoVacio from '@/components/comunes/EstadoVacio.vue';

interface Seleccion {
  lote: Lote;
  parada: LoteParada;
  tipo: 'entrada' | 'salida';
}

const sesion = useSesionStore();
const ahora = useNow({ scheduler: (actualizar) => useIntervalFn(actualizar, 30_000) });

const lotes = ref<Lote[]>([]);
const cargando = ref(true);
const seleccion = ref<Seleccion | null>(null);
const dialogo = ref(false);

const misAreas = computed(() => sesion.usuario?.areas ?? []);
const areaElegida = ref<string | null>(null);

watch(
  misAreas,
  (areas) => {
    if (!areas.some((area) => area.codigo === areaElegida.value)) {
      areaElegida.value = areas[0]?.codigo ?? null;
    }
  },
  { immediate: true },
);

const area = computed(() => misAreas.value.find((actual) => actual.codigo === areaElegida.value));

const enArea = computed(() => (area.value ? lotesEnArea(lotes.value, area.value.codigo) : []));
const porLlegar = computed(() =>
  area.value ? lotesPorLlegar(lotes.value, area.value.codigo) : [],
);
const movimientos = computed(() =>
  area.value ? movimientosDeHoy(lotes.value, area.value.codigo, ahora.value) : [],
);

const fechaLarga = new Intl.DateTimeFormat('es-MX', {
  weekday: 'long',
  day: 'numeric',
  month: 'long',
});
const reloj = new Intl.DateTimeFormat('es-MX', {
  hour: '2-digit',
  minute: '2-digit',
  hourCycle: 'h23',
});

const hoy = computed(() => {
  const texto = `${fechaLarga.format(ahora.value)} · ${reloj.format(ahora.value)}`;
  return texto.charAt(0).toUpperCase() + texto.slice(1);
});

const hora = (iso: string | null): string => (iso ? reloj.format(new Date(iso)) : '');

const transcurrido = (iso: string | null): string => {
  if (!iso) return '';
  const minutos = Math.max(
    Math.floor((ahora.value.getTime() - new Date(iso).getTime()) / 60000),
    0,
  );
  const horas = Math.floor(minutos / 60);
  return horas ? `${horas} h ${minutos % 60} min` : `${minutos} min`;
};

const detalleAhora = (lote: Lote, parada: LoteParada): string =>
  [
    `Entró a las ${hora(parada.entrada_ocurrido_en)}`,
    transcurrido(parada.entrada_ocurrido_en),
    `${parada.piezas_recibidas ?? 0} piezas recibidas`,
    `parada ${parada.orden} de ${lote.paradas.length}`,
  ].join(' · ');

const detallePorLlegar = (
  lote: Lote,
  anterior: LoteParada | undefined,
  disponible: boolean,
): string => {
  if (!anterior) return `Lote nuevo · ${lote.cantidad_objetivo} piezas`;
  if (!disponible && !anterior.salida_ocurrido_en) {
    return `En ${anterior.area.nombre} desde las ${hora(anterior.entrada_ocurrido_en)} · ${anterior.piezas_recibidas ?? 0} piezas`;
  }
  return `${anterior.area.nombre} entregó ${anterior.piezas_entregadas ?? 0} piezas`;
};

const cargar = async (): Promise<void> => {
  const { data, error } = await useApi<Lote[]>('/lotes?activos=1').json<Lote[]>();
  cargando.value = false;
  if (!error.value) lotes.value = data.value ?? [];
};

const reemplazar = (nuevo: Lote): void => {
  lotes.value = lotes.value.map((lote) => (lote.codigo === nuevo.codigo ? nuevo : lote));
};

const capturar = (lote: Lote, parada: LoteParada, tipo: 'entrada' | 'salida'): void => {
  seleccion.value = { lote, parada, tipo };
  dialogo.value = true;
};

const { recientes } = useLotesEnVivo(useCanalesDeLotes(), (evento) =>
  actualizarEnLista(lotes, evento.codigo),
);

useIntervalFn(() => void cargar(), 120_000);
onMounted(cargar);
</script>

<template>
  <q-page padding>
    <div class="mi-area">
      <EstadoVacio v-if="!misAreas.length" icono="handyman" titulo="No tienes áreas asignadas" />

      <template v-else>
        <div class="row items-center justify-between q-mb-md">
          <div class="row items-center no-wrap q-gutter-x-sm">
            <q-avatar
              v-if="area"
              size="40px"
              color="grey-3"
              text-color="dark"
              :icon="iconoArea(area.codigo)"
            />
            <h1 class="text-h4 q-my-none">{{ area?.nombre }}</h1>
          </div>
          <div class="text-secundario">{{ hoy }}</div>
        </div>

        <q-tabs
          v-if="misAreas.length > 1"
          v-model="areaElegida"
          align="left"
          active-color="primary"
          indicator-color="primary"
          no-caps
          class="text-secundario q-mb-md"
        >
          <q-tab
            v-for="opcion in misAreas"
            :key="opcion.codigo"
            :name="opcion.codigo"
            :label="opcion.nombre"
          />
        </q-tabs>

        <div class="entrada-escalonada">
          <q-card flat bordered class="q-mb-md">
            <q-card-section>
              <div class="row items-center justify-between q-mb-sm">
                <h2 class="text-h6 q-my-none">En {{ area?.nombre }} ahora</h2>
                <span class="text-caption text-secundario">
                  {{ enArea.length }} {{ enArea.length === 1 ? 'lote' : 'lotes' }}
                </span>
              </div>
              <TransitionGroup name="tarjeta" tag="div" class="column q-gutter-y-sm">
                <div
                  v-for="item in enArea"
                  :key="`ahora-${item.lote.id}`"
                  class="tarjeta row items-center q-pa-md"
                  :class="{ destello: recientes.includes(item.lote.codigo) }"
                >
                  <div class="col">
                    <div class="row items-center q-gutter-x-sm">
                      <router-link
                        :to="`/lotes/${item.lote.codigo}`"
                        class="text-h6 codigo text-dark enlace-lote"
                      >
                        {{ item.lote.codigo }}
                      </router-link>
                      <span>{{ item.lote.producto.nombre }}</span>
                      <ChipEstado :estado="item.lote.estado" />
                    </div>
                    <div class="text-caption text-secundario">
                      {{ detalleAhora(item.lote, item.parada) }}
                    </div>
                  </div>
                  <q-btn
                    v-if="item.disponible"
                    unelevated
                    no-caps
                    color="primary"
                    icon="check"
                    label="Registrar salida"
                    @click="capturar(item.lote, item.parada, 'salida')"
                  />
                </div>
              </TransitionGroup>
              <div v-if="!enArea.length && !cargando" class="text-secundario q-py-sm">
                Ningún lote en el área
              </div>
            </q-card-section>
          </q-card>

          <q-card flat bordered class="q-mb-md">
            <q-card-section>
              <div class="row items-center justify-between q-mb-sm">
                <h2 class="text-h6 q-my-none">Por llegar</h2>
                <span class="text-caption text-secundario">
                  {{ porLlegar.length }} {{ porLlegar.length === 1 ? 'lote' : 'lotes' }}
                </span>
              </div>
              <TransitionGroup name="tarjeta" tag="div" class="column q-gutter-y-sm">
                <div
                  v-for="item in porLlegar"
                  :key="`llegar-${item.lote.id}`"
                  class="tarjeta row items-center q-pa-md"
                  :class="{ destello: recientes.includes(item.lote.codigo) }"
                >
                  <div class="col">
                    <div class="row items-center q-gutter-x-sm">
                      <router-link
                        :to="`/lotes/${item.lote.codigo}`"
                        class="text-h6 codigo text-dark enlace-lote"
                      >
                        {{ item.lote.codigo }}
                      </router-link>
                      <span>{{ item.lote.producto.nombre }}</span>
                      <ChipEstado :estado="item.lote.estado" />
                    </div>
                    <div class="text-caption text-secundario">
                      {{ detallePorLlegar(item.lote, item.anterior, item.disponible) }}
                    </div>
                  </div>
                  <q-btn
                    unelevated
                    no-caps
                    color="primary"
                    icon="login"
                    label="Registrar entrada"
                    :disable="!item.disponible"
                    @click="capturar(item.lote, item.parada, 'entrada')"
                  />
                </div>
              </TransitionGroup>
              <div v-if="!porLlegar.length && !cargando" class="text-secundario q-py-sm">
                Ningún lote en camino
              </div>
            </q-card-section>
          </q-card>

          <q-card flat bordered>
            <q-card-section>
              <h2 class="text-h6 q-mt-none q-mb-sm">Movimientos de hoy en {{ area?.nombre }}</h2>
              <q-markup-table v-if="movimientos.length" flat separator="horizontal">
                <thead class="bg-secondary text-white">
                  <tr>
                    <th class="text-left">Hora</th>
                    <th class="text-left">Evento</th>
                    <th class="text-left">Lote</th>
                    <th class="text-right">Piezas</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="movimiento in movimientos" :key="movimiento.clave">
                    <td class="codigo">{{ hora(movimiento.hora) }}</td>
                    <td>{{ movimiento.evento }}</td>
                    <td>
                      <span class="codigo">{{ movimiento.lote.codigo }}</span> ·
                      {{ movimiento.lote.producto.nombre }}
                    </td>
                    <td class="text-right codigo">{{ movimiento.piezas ?? '' }}</td>
                  </tr>
                </tbody>
              </q-markup-table>
              <div v-else-if="!cargando" class="text-secundario q-py-sm">Sin movimientos hoy</div>
            </q-card-section>
          </q-card>
        </div>
      </template>
    </div>

    <DialogoCaptura
      v-if="seleccion"
      v-model="dialogo"
      :lote="seleccion.lote"
      :parada="seleccion.parada"
      :tipo="seleccion.tipo"
      @guardado="reemplazar"
    />
  </q-page>
</template>

<style scoped lang="scss">
.mi-area {
  max-width: 960px;
  margin: 0 auto;
}

.enlace-lote {
  text-decoration: none;

  &:hover {
    color: $primary !important;
  }
}

.tarjeta {
  background: $blanco-humo;
  border-radius: 8px;
}

.tarjeta-move {
  transition: transform $duracion-media $curva-salida;
}

.tarjeta-enter-active {
  transition:
    opacity $duracion-media $curva-salida,
    transform $duracion-media $curva-salida;
}

.tarjeta-leave-active {
  position: absolute;
  width: 100%;
  transition: opacity $duracion-rapida ease-in;
}

.tarjeta-enter-from {
  opacity: 0;
  transform: translateY(8px);
}

.tarjeta-leave-to {
  opacity: 0;
}
</style>
