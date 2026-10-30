<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue';
import { useQuasar } from 'quasar';
import { CATEGORIAS_INSUMO, type CategoriaInsumo, type Insumo } from '@aleacion-prima/types';
import { useApi } from '@/services/api';

interface FormularioInsumo {
  codigo: string;
  nombre: string;
  categoria: CategoriaInsumo | null;
  unidad: string;
  costo_unitario: number | null;
  stock_minimo: number | null;
  activo: boolean | null;
}

interface ErrorApi {
  message?: string;
  errors?: Record<string, string[]>;
}

const ETIQUETAS_CATEGORIA: Record<CategoriaInsumo, string> = {
  metal: 'Metal',
  moldeo: 'Moldeo',
  empaque: 'Empaque',
};

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
  unidad: '',
  costo_unitario: null,
  stock_minimo: null,
  activo: null,
});

/* ---------- Formato ---------- */

const dinero = new Intl.NumberFormat('es-MX', {
  style: 'currency',
  currency: 'MXN',
  minimumFractionDigits: 2,
  maximumFractionDigits: 4,
});
const cantidades = new Intl.NumberFormat('es-MX', { maximumFractionDigits: 3 });

const formatearCosto = (valor: string): string => dinero.format(Number(valor));
const formatearCantidad = (valor: string, unidad: string): string =>
  `${cantidades.format(Number(valor))} ${unidad}`;

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
  formulario.unidad = insumo?.unidad ?? '';
  formulario.costo_unitario = insumo ? Number(insumo.costo_unitario) : null;
  formulario.stock_minimo = insumo ? Number(insumo.stock_minimo) : null;
  formulario.activo = insumo ? insumo.activo : null;
  dialogo.value = true;
};

const guardar = async (): Promise<void> => {
  const esEdicion = editando.value !== null;
  const cuerpo = {
    codigo: formulario.codigo,
    nombre: formulario.nombre,
    categoria: formulario.categoria,
    unidad: formulario.unidad,
    costo_unitario: formulario.costo_unitario,
    stock_minimo: formulario.stock_minimo,
    // Sin estado elegido, el alta lo toma como activo.
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
  $q.notify({ type: 'positive', message: esEdicion ? 'Insumo actualizado' : 'Insumo creado' });
};

/* ---------- Desactivar ---------- */

const desactivar = (insumo: Insumo): void => {
  $q.dialog({
    title: 'Desactivar insumo',
    message: `${insumo.codigo} · ${insumo.nombre} dejará de ofrecerse en recetas nuevas. Podrás reactivarlo editándolo.`,
    cancel: { label: 'Cancelar', flat: true },
    ok: { label: 'Desactivar', color: 'negative', unelevated: true },
    persistent: true,
  }).onOk(() => {
    void (async () => {
      const { error, data } = await useApi(`/insumos/${insumo.id}`).delete().json<Insumo>();
      if (error.value) {
        $q.notify({
          type: 'negative',
          message: textoError(data.value, 'No se pudo desactivar el insumo'),
        });
        return;
      }
      await cargar();
      $q.notify({ type: 'positive', message: 'Insumo desactivado' });
    })();
  });
};

onMounted(cargar);
</script>

<template>
  <q-page padding>
    <div class="row items-center justify-between q-mb-lg">
      <div>
        <div class="text-h4">Insumos</div>
        <div class="text-body2 text-grey-7">
          Administra el catálogo de materias primas, su costo unitario y su stock mínimo.
        </div>
      </div>
      <q-btn
        outline
        color="primary"
        icon="add"
        label="Nuevo insumo"
        :disable="cargando"
        @click="abrir()"
      />
    </div>

    <q-linear-progress v-if="cargando" indeterminate color="primary" />

    <div v-else-if="insumos.length === 0" class="text-grey-7">Aún no hay insumos.</div>

    <div v-else class="row q-col-gutter-md">
      <div v-for="insumo in insumos" :key="insumo.id" class="col-12 col-sm-6 col-lg-4">
        <q-card flat bordered class="full-height column">
          <q-card-section class="row items-start no-wrap">
            <div class="col">
              <div class="text-caption text-grey-7">{{ insumo.codigo }}</div>
              <div class="text-h6">{{ insumo.nombre }}</div>
            </div>
            <q-badge :color="insumo.activo ? 'positive' : 'grey'">{{
              insumo.activo ? 'Activo' : 'Inactivo'
            }}</q-badge>
          </q-card-section>

          <q-card-section class="column q-gutter-sm q-pt-none">
            <div class="bg-grey-2 rounded-borders q-pa-sm row">
              <div class="col-6">
                <div class="text-caption text-grey-7">Categoría</div>
                <div>{{ ETIQUETAS_CATEGORIA[insumo.categoria] }}</div>
              </div>
              <div class="col-6">
                <div class="text-caption text-grey-7">Costo unitario</div>
                <div>{{ formatearCosto(insumo.costo_unitario) }} / {{ insumo.unidad }}</div>
              </div>
            </div>
            <div class="bg-grey-2 rounded-borders q-pa-sm row">
              <div class="col-6">
                <div class="text-caption text-grey-7">Stock mínimo</div>
                <div>{{ formatearCantidad(insumo.stock_minimo, insumo.unidad) }}</div>
              </div>
              <div class="col-6">
                <div class="text-caption text-grey-7">Existencia</div>
                <div>
                  {{ formatearCantidad(insumo.existencia, insumo.unidad) }}
                  <q-badge v-if="insumo.bajo_minimo" color="negative" class="q-ml-xs"
                    >Bajo mínimo</q-badge
                  >
                </div>
              </div>
            </div>
          </q-card-section>

          <q-space />
          <q-card-actions align="right">
            <q-btn
              v-if="insumo.activo"
              flat
              color="negative"
              label="Desactivar"
              @click="desactivar(insumo)"
            />
            <q-btn outline color="primary" label="Editar insumo" @click="abrir(insumo)" />
          </q-card-actions>
        </q-card>
      </div>
    </div>

    <q-dialog v-model="dialogo" persistent>
      <q-card style="width: 640px; max-width: 95vw">
        <q-card-section class="row items-start no-wrap">
          <div class="col">
            <div class="text-h6">{{ editando ? 'Editar insumo' : 'Nuevo insumo' }}</div>
            <div class="text-caption text-grey-7">
              {{
                editando
                  ? 'Actualiza los datos, el costo y el stock mínimo del insumo.'
                  : 'Completa los datos, el costo y el stock mínimo del insumo.'
              }}
            </div>
          </div>
          <q-btn v-close-popup flat round dense icon="close" aria-label="Cerrar" />
        </q-card-section>
        <q-separator />

        <q-card-section class="scroll" style="max-height: 68vh">
          <div class="text-subtitle1 text-weight-bold q-mb-sm">Datos del insumo</div>
          <div class="row q-col-gutter-md">
            <q-input
              v-model="formulario.codigo"
              class="col-12 col-sm-4"
              label="Código"
              outlined
              :error="campoError('codigo') !== ''"
              :error-message="campoError('codigo')"
            />
            <q-input
              v-model="formulario.nombre"
              class="col-12 col-sm-8"
              label="Nombre del insumo"
              outlined
              :error="campoError('nombre') !== ''"
              :error-message="campoError('nombre')"
            />
            <q-select
              v-model="formulario.categoria"
              class="col-12 col-sm-4"
              :options="opcionesCategoria"
              label="Categoría"
              emit-value
              map-options
              outlined
              :error="campoError('categoria') !== ''"
              :error-message="campoError('categoria')"
            />
            <q-input
              v-model="formulario.unidad"
              class="col-12 col-sm-4"
              label="Unidad"
              hint="Por ejemplo: kg, pieza"
              outlined
              :error="campoError('unidad') !== ''"
              :error-message="campoError('unidad')"
            />
            <q-select
              v-model="formulario.activo"
              class="col-12 col-sm-4"
              :options="opcionesEstado"
              label="Estado"
              emit-value
              map-options
              outlined
              :hint="editando ? undefined : 'Opciones: Activo · Inactivo'"
              :error="campoError('activo') !== ''"
              :error-message="campoError('activo')"
            />
            <q-input
              v-model.number="formulario.costo_unitario"
              class="col-12 col-sm-6"
              label="Costo unitario"
              type="number"
              step="any"
              prefix="$"
              outlined
              :error="campoError('costo_unitario') !== ''"
              :error-message="campoError('costo_unitario')"
            />
            <q-input
              v-model.number="formulario.stock_minimo"
              class="col-12 col-sm-6"
              label="Stock mínimo"
              type="number"
              step="any"
              :suffix="formulario.unidad"
              outlined
              :error="campoError('stock_minimo') !== ''"
              :error-message="campoError('stock_minimo')"
            />
            <q-input
              v-if="editando"
              :model-value="formatearCantidad(editando.existencia, editando.unidad)"
              class="col-12"
              label="Existencia"
              hint="Solo lectura: la calculan las entradas y salidas de inventario."
              readonly
              outlined
            />
          </div>
        </q-card-section>

        <q-separator />
        <q-card-actions align="right">
          <q-btn v-close-popup outline color="primary" label="Cancelar" />
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
