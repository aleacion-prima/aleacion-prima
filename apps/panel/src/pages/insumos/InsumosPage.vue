<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue';
import { useQuasar } from 'quasar';
import EstadoVacio from '@/components/comunes/EstadoVacio.vue';
import { formatearCantidad, formatearMoneda } from '@/composables/formato';
import { CATEGORIAS_INSUMO, type CategoriaInsumo, type Insumo } from '@aleacion-prima/types';
import { useApi } from '@/services/api';

interface FormularioInsumo {
  codigo: string;
  nombre: string;
  categoria: CategoriaInsumo | null;
  unidad: string | null;
  costo_unitario: number | null;
  stock_minimo: number | null;
  activo: boolean | null;
}

interface ErrorApi {
  message?: string;
  errors?: Record<string, string[]>;
}

const conUnidad = (valor: string, unidad: string): string =>
  `${formatearCantidad(valor)} ${unidad}`.trim();

const ETIQUETAS_CATEGORIA: Record<CategoriaInsumo, string> = {
  metal: 'Metal',
  moldeo: 'Moldeo',
  empaque: 'Empaque',
};

const UNIDADES = ['kg', 'g', 'l', 'pieza', 'caja'];

const opcionesCategoria = CATEGORIAS_INSUMO.map((valor) => ({
  label: ETIQUETAS_CATEGORIA[valor],
  value: valor,
}));

const opcionesEstado = [
  { label: 'Activo', value: true },
  { label: 'Inactivo', value: false },
];

const $q = useQuasar();
const insumos = ref<Insumo[]>([]);
const dialogo = ref(false);
const editando = ref<Insumo | null>(null);
const cargando = ref(true);
const guardando = ref(false);
const errores = ref<Record<string, string[]>>({});
const formulario = reactive<FormularioInsumo>({
  codigo: '',
  nombre: '',
  categoria: null,
  unidad: null,
  costo_unitario: null,
  stock_minimo: null,
  activo: null,
});

/* ---------- Formato ---------- */

/** Existencia que se muestra en el modal: la real al editar, cero al registrar. */
const existenciaModal = computed(() =>
  editando.value
    ? conUnidad(editando.value.existencia, editando.value.unidad)
    : conUnidad('0', formulario.unidad ?? ''),
);

/** Un insumo con una unidad fuera de la lista no la pierde al editarlo. */
const opcionesUnidad = computed<string[]>(() => {
  const actual = formulario.unidad;
  return actual !== null && actual !== '' && !UNIDADES.includes(actual)
    ? [...UNIDADES, actual]
    : UNIDADES;
});

/* ---------- Errores de la API ---------- */

const textoError = (cuerpo: unknown, porDefecto: string): string => {
  const detalle = cuerpo as ErrorApi | null;
  const primero = Object.values(detalle?.errors ?? {})[0]?.[0];
  return primero ?? detalle?.message ?? porDefecto;
};

const campoError = (campo: string): string => errores.value[campo]?.[0] ?? '';

/* ---------- Carga ---------- */

const cargar = async (): Promise<void> => {
  cargando.value = true;
  const { data, error } = await useApi<Insumo[]>('/insumos').json<Insumo[]>();
  cargando.value = false;

  if (error.value) {
    $q.notify({ type: 'negative', message: 'No se pudieron cargar los insumos' });
    return;
  }
  insumos.value = data.value ?? [];
};

/* ---------- Diálogo ---------- */

const abrir = (insumo?: Insumo): void => {
  editando.value = insumo ?? null;
  errores.value = {};
  formulario.codigo = insumo?.codigo ?? '';
  formulario.nombre = insumo?.nombre ?? '';
  formulario.categoria = insumo?.categoria ?? null;
  formulario.unidad = insumo?.unidad ?? null;
  formulario.costo_unitario = insumo ? Number(insumo.costo_unitario) : null;
  formulario.stock_minimo = insumo ? Number(insumo.stock_minimo) : null;
  formulario.activo = insumo ? insumo.activo : null;
  dialogo.value = true;
};

const confirmarDesactivacion = (insumo: Insumo): Promise<boolean> =>
  new Promise((resolver) => {
    $q.dialog({
      title: 'Desactivar insumo',
      message: `¿Desactivar ${insumo.codigo} · ${insumo.nombre}? Dejará de ofrecerse en recetas nuevas. Podrás reactivarlo editándolo.`,
      cancel: true,
      persistent: true,
    })
      .onOk(() => {
        resolver(true);
      })
      .onCancel(() => {
        resolver(false);
      })
      .onDismiss(() => {
        resolver(false);
      });
  });

const guardar = async (): Promise<void> => {
  const insumo = editando.value;
  if (insumo?.activo && formulario.activo === false && !(await confirmarDesactivacion(insumo))) {
    return;
  }

  const esEdicion = editando.value !== null;
  const cuerpo = {
    codigo: formulario.codigo,
    nombre: formulario.nombre,
    categoria: formulario.categoria,
    unidad: formulario.unidad,
    costo_unitario: formulario.costo_unitario,
    stock_minimo: formulario.stock_minimo,
    ...(formulario.activo !== null ? { activo: formulario.activo } : {}),
  };

  guardando.value = true;
  errores.value = {};
  const { error, data } = esEdicion
    ? await useApi(`/insumos/${editando.value?.id ?? ''}`)
        .put(cuerpo)
        .json<Insumo>()
    : await useApi('/insumos').post(cuerpo).json<Insumo>();
  guardando.value = false;

  if (error.value) {
    errores.value = (data.value as ErrorApi | null)?.errors ?? {};
    $q.notify({
      type: 'negative',
      message: textoError(data.value, 'No se pudo guardar el insumo'),
    });
    return;
  }
  dialogo.value = false;
  await cargar();
  $q.notify({ type: 'positive', message: esEdicion ? 'Insumo actualizado' : 'Insumo registrado' });
};

onMounted(cargar);
</script>

<template>
  <q-page padding>
    <div class="row items-center justify-between q-mb-lg">
      <div>
        <div class="text-h4 catalogo-titulo">Catálogo de insumos</div>
        <div class="text-body2 text-secundario">
          Administra los insumos con su código, costo unitario y stock mínimo.
        </div>
      </div>
      <q-btn
        outline
        color="primary"
        icon="add"
        label="Registrar insumo"
        :disable="cargando"
        @click="abrir()"
      />
    </div>

    <q-linear-progress v-if="cargando" indeterminate color="primary" />

    <EstadoVacio v-else-if="insumos.length === 0" titulo="Aún no hay insumos" />

    <div v-else class="row q-col-gutter-md">
      <div v-for="insumo in insumos" :key="insumo.id" class="col-12 col-sm-6 col-md-4 col-lg-3">
        <q-card flat bordered class="catalogo-tarjeta full-height column">
          <q-card-section class="row items-start no-wrap">
            <div class="col">
              <div class="catalogo-codigo">{{ insumo.codigo }}</div>
              <div class="catalogo-nombre">{{ insumo.nombre }}</div>
            </div>
            <q-chip
              v-if="!insumo.activo"
              class="q-ma-none"
              color="grey-3"
              text-color="grey-8"
              label="Inactivo"
            />
          </q-card-section>

          <q-card-section class="catalogo-campos q-pt-none">
            <div class="catalogo-fila">
              <div class="catalogo-campo">
                <div class="catalogo-etiqueta">Costo unitario</div>
                <div class="catalogo-valor catalogo-valor--mono">
                  {{ formatearMoneda(insumo.costo_unitario) }} / {{ insumo.unidad }}
                </div>
              </div>
              <div class="catalogo-campo">
                <div class="catalogo-etiqueta">Stock mínimo</div>
                <div class="catalogo-valor catalogo-valor--mono">
                  {{ conUnidad(insumo.stock_minimo, insumo.unidad) }}
                </div>
              </div>
            </div>
            <div class="catalogo-campo">
              <div class="catalogo-etiqueta">Existencias actuales</div>
              <div class="catalogo-valor catalogo-valor--mono">
                {{ conUnidad(insumo.existencia, insumo.unidad) }}
                <q-chip
                  v-if="insumo.bajo_minimo"
                  dense
                  color="negative"
                  text-color="white"
                  class="q-ml-xs"
                  label="Bajo mínimo"
                />
              </div>
            </div>
          </q-card-section>

          <q-space />
          <q-card-actions align="right">
            <q-btn
              class="catalogo-boton"
              outline
              no-caps
              color="secondary"
              label="Editar insumo"
              @click="abrir(insumo)"
            />
          </q-card-actions>
        </q-card>
      </div>
    </div>

    <q-dialog v-model="dialogo" persistent>
      <q-card style="width: 640px; max-width: 95vw">
        <q-card-section class="row items-start no-wrap">
          <div class="col">
            <div class="text-h6">{{ editando ? 'Editar insumo' : 'Registrar insumo' }}</div>
            <div class="text-caption text-secundario">
              {{
                editando
                  ? 'Actualiza los datos del insumo en el catálogo.'
                  : 'Completa los datos para agregar un insumo al catálogo.'
              }}
            </div>
          </div>
          <q-btn v-close-popup flat round dense icon="close" aria-label="Cerrar" />
        </q-card-section>
        <q-separator />

        <q-card-section class="scroll" style="max-height: 68vh">
          <div class="row q-col-gutter-md">
            <q-input
              v-model="formulario.codigo"
              class="col-12 col-sm-6"
              label="Código"
              placeholder="Ingresa un código único"
              stack-label
              outlined
              :error="campoError('codigo') !== ''"
              :error-message="campoError('codigo')"
            />
            <q-select
              v-model="formulario.unidad"
              class="col-12 col-sm-6"
              :options="opcionesUnidad"
              label="Unidad"
              placeholder="Selecciona una unidad"
              stack-label
              outlined
              :error="campoError('unidad') !== ''"
              :error-message="campoError('unidad')"
            />
            <q-input
              v-model="formulario.nombre"
              class="col-12"
              label="Nombre del insumo"
              placeholder="Escribe el nombre del insumo"
              stack-label
              outlined
              :error="campoError('nombre') !== ''"
              :error-message="campoError('nombre')"
            />
            <q-select
              v-model="formulario.categoria"
              class="col-sm-6"
              :options="opcionesCategoria"
              label="Categoría"
              emit-value
              map-options
              outlined
              :error="campoError('categoria') !== ''"
              :error-message="campoError('categoria')"
            />
            <q-select
              v-model="formulario.activo"
              class="col-12 col-sm-6"
              :options="opcionesEstado"
              label="Estado"
              emit-value
              map-options
              outlined
              :error="campoError('activo') !== ''"
              :error-message="campoError('activo')"
            />
            <q-input
              v-model.number="formulario.costo_unitario"
              class="col-12 col-sm-6"
              label="Costo unitario"
              placeholder="Ingresa el costo por unidad"
              stack-label
              type="number"
              step="any"
              prefix="$"
              :suffix="formulario.unidad ? `/ ${formulario.unidad}` : undefined"
              outlined
              :error="campoError('costo_unitario') !== ''"
              :error-message="campoError('costo_unitario')"
            />
            <q-input
              v-model.number="formulario.stock_minimo"
              class="col-12 col-sm-6"
              label="Stock mínimo"
              placeholder="Define el mínimo requerido"
              stack-label
              type="number"
              step="any"
              :suffix="formulario.unidad ?? undefined"
              outlined
              hint="El costo y el stock mínimo se expresan en la unidad seleccionada."
              :error="campoError('stock_minimo') !== ''"
              :error-message="campoError('stock_minimo')"
            />
            <q-input
              :model-value="existenciaModal"
              class="col-12"
              label="Existencia"
              readonly
              outlined
            />
          </div>
        </q-card-section>

        <q-separator />
        <q-card-actions align="right">
          <q-btn v-close-popup outline color="primary" label="Cancelar" :disable="guardando" />
          <q-btn
            color="primary"
            unelevated
            :label="editando ? 'Guardar cambios' : 'Registrar insumo'"
            :loading="guardando"
            @click="guardar"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>
