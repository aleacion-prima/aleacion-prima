<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import type { Lote } from '@aleacion-prima/types';
import { useApi } from '@/services/api';
import { formatearMoneda } from '@/composables/formato';
import { estadoDeParada, paradasDeLote, siguienteCaptura } from '@/composables/recorridoLote';
import ChipEstado from '@/components/lotes/ChipEstado.vue';
import RecorridoLote from '@/components/lotes/RecorridoLote.vue';
import EstadoVacio from '@/components/comunes/EstadoVacio.vue';
import NumeroAnimado from '@/components/comunes/NumeroAnimado.vue';
import DialogoCaptura from '@/components/lotes/DialogoCaptura.vue';

const route = useRoute();

const lote = ref<Lote | null>(null);
const capturando = ref(false);

const captura = computed(() => {
  if (!lote.value) return null;
  const siguiente = siguienteCaptura(lote.value);
  return siguiente?.parada.puede_capturar ? siguiente : null;
});

const etiquetaCaptura = computed(() => {
  if (!captura.value) return '';
  const area = captura.value.parada.area.nombre;
  return captura.value.tipo === 'entrada'
    ? `Registrar entrada a ${area}`
    : `Registrar salida de ${area}`;
});
const cargando = ref(true);
const noEncontrado = ref(false);

const fecha = new Intl.DateTimeFormat('es-MX', { dateStyle: 'medium', timeStyle: 'short' });

const paradaActual = computed(() =>
  lote.value?.paradas.find((parada) => estadoDeParada(parada) === 'en_curso'),
);

const ubicacion = computed(() => {
  if (!lote.value) return '';
  if (paradaActual.value) return paradaActual.value.area.nombre;
  if (lote.value.estado === 'creado') return 'Sin iniciar';
  if (lote.value.estado === 'cancelado') return 'Cancelado';
  if (lote.value.estado === 'en_proceso') {
    const siguiente = lote.value.paradas.find((parada) => !parada.entrada_ocurrido_en);
    return siguiente ? `En tránsito a ${siguiente.area.nombre}` : 'En tránsito';
  }
  return 'Recorrido completo';
});

const ordenActual = computed(() => {
  if (!lote.value) return 0;
  return paradaActual.value
    ? paradaActual.value.orden
    : lote.value.paradas.filter((parada) => parada.salida_ocurrido_en).length;
});

const piezasActuales = computed(() => {
  const paradas = lote.value?.paradas ?? [];
  const conPiezas = [...paradas]
    .reverse()
    .find((parada) => parada.piezas_entregadas !== null || parada.piezas_recibidas !== null);
  if (!conPiezas) return { numero: lote.value?.cantidad_objetivo ?? 0, texto: 'objetivo' };
  return conPiezas.piezas_entregadas !== null
    ? { numero: conPiezas.piezas_entregadas, texto: 'entregadas' }
    : { numero: conPiezas.piezas_recibidas ?? 0, texto: 'recibidas' };
});

const cargar = async (): Promise<void> => {
  const codigo = String(route.params.codigo ?? '');
  const { data, error, statusCode } = await useApi<Lote>(
    `/lotes/${encodeURIComponent(codigo)}`,
  ).json<Lote>();
  cargando.value = false;
  noEncontrado.value = Boolean(error.value) && statusCode.value === 404;
  lote.value = error.value ? null : data.value;
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

    <div v-if="cargando" class="q-pa-xl flex flex-center">
      <q-spinner color="primary" size="40px" />
    </div>

    <EstadoVacio
      v-else-if="!lote"
      :icono="noEncontrado ? 'search_off' : 'cloud_off'"
      :titulo="noEncontrado ? 'Ese lote no existe' : 'No se pudo cargar el lote'"
    >
      <q-btn v-if="!noEncontrado" flat color="primary" label="Reintentar" @click="cargar" />
    </EstadoVacio>

    <div v-else class="entrada-escalonada">
      <div class="row items-center justify-between q-gutter-y-sm">
        <div class="row items-center q-gutter-x-md">
          <h1 class="text-h4 q-my-none codigo">{{ lote.codigo }}</h1>
          <ChipEstado :key="lote.estado" :estado="lote.estado" class="rebotar" />
        </div>
        <transition name="pagina" mode="out-in">
          <q-btn
            v-if="captura"
            :key="etiquetaCaptura"
            unelevated
            no-caps
            color="primary"
            :icon="captura.tipo === 'entrada' ? 'login' : 'check'"
            :label="etiquetaCaptura"
            @click="capturando = true"
          />
        </transition>
      </div>
      <div class="text-secundario q-mb-lg">
        {{ lote.producto.nombre }} · <span class="codigo">{{ lote.producto.codigo }}</span> ·
        {{ lote.cantidad_objetivo }} piezas · creado el
        {{ fecha.format(new Date(lote.created_at)) }}
      </div>

      <div class="row q-col-gutter-md q-mb-md entrada-escalonada">
        <div class="col-12 col-sm-6 col-md-4">
          <q-card flat bordered class="full-height">
            <q-card-section>
              <div class="text-overline text-secundario">Área actual</div>
              <div class="text-h6">
                {{ ubicacion }}
              </div>
              <div class="text-caption text-secundario">
                Parada {{ ordenActual }} de {{ lote.paradas.length }}
              </div>
            </q-card-section>
          </q-card>
        </div>
        <div class="col-12 col-sm-6 col-md-4">
          <q-card flat bordered class="full-height">
            <q-card-section>
              <div class="text-overline text-secundario">Piezas</div>
              <div class="text-h6">
                <NumeroAnimado :valor="piezasActuales.numero" formato="entero" />
                {{ piezasActuales.texto }}
              </div>
            </q-card-section>
          </q-card>
        </div>
        <div v-if="lote.costo_total !== undefined" class="col-12 col-sm-6 col-md-4">
          <q-card flat bordered class="full-height">
            <q-card-section>
              <div class="text-overline text-secundario">Costo a la fecha</div>
              <div class="text-h6 codigo">
                <NumeroAnimado :valor="lote.costo_total" formato="moneda" />
              </div>
            </q-card-section>
          </q-card>
        </div>
      </div>

      <q-card flat bordered class="q-mb-md">
        <q-card-section>
          <h2 class="text-h6 q-mt-none q-mb-sm">Recorrido</h2>
          <RecorridoLote :paradas="paradasDeLote(lote)" />
        </q-card-section>
      </q-card>

      <q-card v-if="lote.insumos?.length" flat bordered>
        <q-card-section>
          <h2 class="text-h6 q-mt-none q-mb-md">Insumos asignados</h2>
          <q-markup-table flat separator="horizontal">
            <thead class="bg-secondary text-white">
              <tr>
                <th class="text-left">Código</th>
                <th class="text-left">Insumo</th>
                <th class="text-right">Cantidad</th>
                <th v-if="lote.insumos[0]?.costo_unitario !== undefined" class="text-right">
                  Costo unitario
                </th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="asignado in lote.insumos" :key="asignado.id">
                <td class="codigo">{{ asignado.insumo.codigo }}</td>
                <td>{{ asignado.insumo.nombre }}</td>
                <td class="text-right codigo">
                  <NumeroAnimado :valor="asignado.cantidad" /> {{ asignado.insumo.unidad }}
                </td>
                <td v-if="asignado.costo_unitario !== undefined" class="text-right codigo">
                  {{ formatearMoneda(asignado.costo_unitario) }}
                </td>
              </tr>
            </tbody>
          </q-markup-table>
        </q-card-section>
      </q-card>
    </div>

    <DialogoCaptura
      v-if="lote && captura"
      v-model="capturando"
      :lote="lote"
      :parada="captura.parada"
      :tipo="captura.tipo"
      @guardado="lote = $event"
    />
  </q-page>
</template>
