<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import type { AjusteLote, Lote, LoteInsumoAsignado } from '@aleacion-prima/types';
import { useApi } from '@/services/api';
import { conPlural, formatearCantidad, formatearMoneda } from '@/composables/formato';
import { estadoDeParada, paradasDeLote, siguienteCaptura } from '@/composables/recorridoLote';
import { useCanalesDeLotes, useLotesEnVivo } from '@/composables/lotesEnVivo';
import { etiquetaCampo, type ObjetivoAjuste } from '@/catalogos/ajustes';
import type { EstadoParada } from '@/catalogos/areas';
import ChipEstado from '@/components/lotes/ChipEstado.vue';
import RecorridoLote from '@/components/lotes/RecorridoLote.vue';
import EstadoVacio from '@/components/comunes/EstadoVacio.vue';
import NumeroAnimado from '@/components/comunes/NumeroAnimado.vue';
import DialogoCaptura from '@/components/lotes/DialogoCaptura.vue';
import DialogoConfirmarCierre from '@/components/lotes/DialogoConfirmarCierre.vue';
import DialogoCancelarLote from '@/components/lotes/DialogoCancelarLote.vue';
import DialogoAjusteLote from '@/components/lotes/DialogoAjusteLote.vue';
import { useSesionStore } from '@/stores/sesion';

const route = useRoute();

const lote = ref<Lote | null>(null);
const ajustes = ref<AjusteLote[]>([]);
const capturando = ref(false);
const confirmando = ref(false);
const cancelando = ref(false);
const ajustando = ref(false);
const objetivoAjuste = ref<ObjetivoAjuste | null>(null);
const sesion = useSesionStore();

const esAdmin = computed(() => sesion.usuario?.rol === 'administrador');
const puedeConfirmar = computed(() => esAdmin.value && lote.value?.estado === 'por_confirmar');
const puedeCancelar = computed(
  () => esAdmin.value && (lote.value?.estado === 'creado' || lote.value?.estado === 'en_proceso'),
);

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
    ? {
        numero: conPiezas.piezas_entregadas,
        texto: conPlural(conPiezas.piezas_entregadas, 'entregada', 'entregadas'),
      }
    : {
        numero: conPiezas.piezas_recibidas ?? 0,
        texto: conPlural(conPiezas.piezas_recibidas ?? 0, 'recibida', 'recibidas'),
      };
});

const cargar = async (): Promise<void> => {
  const codigo = String(route.params.codigo ?? '');
  const { data, error, statusCode } = await useApi<Lote>(
    `/lotes/${encodeURIComponent(codigo)}`,
  ).json<Lote>();
  cargando.value = false;
  noEncontrado.value = Boolean(error.value) && statusCode.value === 404;
  lote.value = error.value ? null : data.value;
  if (lote.value && esAdmin.value) void cargarAjustes();
};

const cargarAjustes = async (): Promise<void> => {
  const codigo = String(route.params.codigo ?? '');
  const { data, error } = await useApi<AjusteLote[]>(
    `/lotes/${encodeURIComponent(codigo)}/ajustes`,
  ).json<AjusteLote[]>();
  ajustes.value = error.value || !Array.isArray(data.value) ? [] : data.value;
};

const abrirAjusteParada = (indice: number): void => {
  const parada = lote.value?.paradas[indice];
  if (!parada) return;
  objetivoAjuste.value = { tipo: 'parada', parada };
  ajustando.value = true;
};

const abrirAjusteInsumo = (insumo: LoteInsumoAsignado): void => {
  objetivoAjuste.value = { tipo: 'insumo', insumo };
  ajustando.value = true;
};

const puedeAjustarParada = (estado: EstadoParada): boolean =>
  estado === 'terminada' || estado === 'en_curso' || estado === 'cancelada';

const alGuardarAjuste = (actualizado: Lote): void => {
  lote.value = actualizado;
  void cargarAjustes();
};

const renglonDeAjuste = (ajuste: AjusteLote): string =>
  ajuste.parada
    ? `Parada ${ajuste.parada.orden} · ${ajuste.parada.area.nombre}`
    : ajuste.insumo
      ? `${ajuste.insumo.codigo} · ${ajuste.insumo.nombre}`
      : '—';

const valorDeAjuste = (ajuste: AjusteLote, valor: string | number | null): string => {
  if (valor === null) return '—';
  if (ajuste.campo === 'entrada_ocurrido_en' || ajuste.campo === 'salida_ocurrido_en') {
    return fecha.format(new Date(String(valor)));
  }
  if (ajuste.campo === 'piezas_recibidas' || ajuste.campo === 'piezas_entregadas') {
    return formatearCantidad(valor, 0);
  }
  return formatearCantidad(valor);
};

useLotesEnVivo(useCanalesDeLotes(), (evento) => {
  if (evento.codigo === lote.value?.codigo) void cargar();
});

watch(
  () => route.params.codigo,
  () => {
    cargando.value = true;
    lote.value = null;
    void cargar();
  },
  { immediate: true },
);
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
      <q-btn v-if="!noEncontrado" flat color="primary" label="Reintentar" @click="cargar()" />
    </EstadoVacio>

    <div v-else class="entrada-escalonada">
      <div class="row items-center justify-between q-gutter-y-sm">
        <div class="row items-center q-gutter-x-md">
          <h1 class="text-h4 q-my-none codigo">{{ lote.codigo }}</h1>
          <ChipEstado :key="lote.estado" :estado="lote.estado" class="rebotar" />
        </div>
        <div class="row items-center q-gutter-sm">
          <q-btn
            v-if="puedeCancelar"
            outline
            no-caps
            color="negative"
            icon="block"
            label="Cancelar lote"
            @click="cancelando = true"
          />
          <q-btn
            v-if="puedeConfirmar"
            unelevated
            no-caps
            color="primary"
            icon="task_alt"
            label="Confirmar cierre"
            @click="confirmando = true"
          />
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
      </div>
      <div class="text-secundario q-mb-lg">
        {{ lote.producto.nombre }} · <span class="codigo">{{ lote.producto.codigo }}</span> ·
        {{ lote.cantidad_objetivo }} piezas · creado el
        {{ fecha.format(new Date(lote.created_at)) }}
        <template v-if="lote.confirmado_en">
          · terminado el {{ fecha.format(new Date(lote.confirmado_en)) }}
        </template>
      </div>
      <q-banner
        v-if="lote.estado === 'cancelado' && lote.cancelado_en"
        dense
        rounded
        class="bg-red-1 text-negative q-mb-lg"
      >
        Cancelado el {{ fecha.format(new Date(lote.cancelado_en)) }} · {{ lote.motivo_cancelacion }}
      </q-banner>

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
          <RecorridoLote :paradas="paradasDeLote(lote)">
            <template #accion="{ indice, parada }">
              <q-btn
                v-if="esAdmin && puedeAjustarParada(parada.estado)"
                flat
                dense
                no-caps
                size="sm"
                color="primary"
                icon="tune"
                label="Ajustar"
                class="q-mt-sm"
                @click="abrirAjusteParada(indice)"
              />
            </template>
          </RecorridoLote>
        </q-card-section>
      </q-card>

      <q-card v-if="lote.insumos?.length" flat bordered>
        <q-card-section>
          <h2 class="text-h6 q-mt-none q-mb-md">Insumos asignados</h2>
          <q-markup-table flat separator="horizontal">
            <thead>
              <tr>
                <th class="text-left">Código</th>
                <th class="text-left">Insumo</th>
                <th class="text-right">Cantidad</th>
                <th v-if="lote.insumos[0]?.costo_unitario !== undefined" class="text-right">
                  Costo unitario
                </th>
                <th v-if="esAdmin" class="text-right">Acciones</th>
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
                <td v-if="esAdmin" class="text-right">
                  <q-btn
                    flat
                    dense
                    no-caps
                    size="sm"
                    color="primary"
                    icon="tune"
                    label="Ajustar"
                    @click="abrirAjusteInsumo(asignado)"
                  />
                </td>
              </tr>
            </tbody>
          </q-markup-table>
        </q-card-section>
      </q-card>
      <q-card v-if="esAdmin" flat bordered class="q-mt-md">
        <q-card-section>
          <div class="row items-center justify-between q-mb-md">
            <h2 class="text-h6 q-my-none">Ajustes</h2>
            <span class="text-caption text-secundario">{{ ajustes.length }} registrados</span>
          </div>
          <EstadoVacio
            v-if="!ajustes.length"
            icono="tune"
            titulo="Sin ajustes"
            descripcion="Corrige un dato desde el recorrido o desde los insumos asignados."
          />
          <q-markup-table v-else flat separator="horizontal" wrap-cells>
            <thead>
              <tr>
                <th class="text-left">Fecha</th>
                <th class="text-left">Renglón</th>
                <th class="text-left">Dato</th>
                <th class="text-left">Antes</th>
                <th class="text-left">Después</th>
                <th class="text-left">Motivo</th>
                <th class="text-left">Responsable</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="ajuste in ajustes" :key="ajuste.id">
                <td class="text-no-wrap">{{ fecha.format(new Date(ajuste.created_at)) }}</td>
                <td>{{ renglonDeAjuste(ajuste) }}</td>
                <td>{{ etiquetaCampo(ajuste.campo) }}</td>
                <td class="codigo">{{ valorDeAjuste(ajuste, ajuste.valores.anterior) }}</td>
                <td class="codigo">{{ valorDeAjuste(ajuste, ajuste.valores.nuevo) }}</td>
                <td>{{ ajuste.motivo }}</td>
                <td>{{ ajuste.usuario?.name ?? '—' }}</td>
              </tr>
            </tbody>
          </q-markup-table>
        </q-card-section>
      </q-card>
    </div>

    <DialogoConfirmarCierre
      v-if="lote"
      v-model="confirmando"
      :lote="lote"
      @guardado="lote = $event"
    />
    <DialogoCancelarLote v-if="lote" v-model="cancelando" :lote="lote" @guardado="lote = $event" />

    <DialogoAjusteLote
      v-if="lote && objetivoAjuste"
      v-model="ajustando"
      :lote="lote"
      :objetivo="objetivoAjuste"
      @guardado="alGuardarAjuste"
    />

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
