<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue';
import { useQuasar } from 'quasar';
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

const dinero = new Intl.NumberFormat('es-MX', {
  style: 'currency',
  currency: 'MXN',
  minimumFractionDigits: 2,
  maximumFractionDigits: 4,
});
const cantidades = new Intl.NumberFormat('es-MX', { maximumFractionDigits: 3 });

const formatearCosto = (valor: string): string => dinero.format(Number(valor));
const formatearCantidad = (valor: string, unidad: string): string =>
  `${cantidades.format(Number(valor))} ${unidad}`.trim();

/** Existencia que se muestra en el modal: la real al editar, cero al registrar. */
const existenciaModal = computed(() =>
  editando.value
    ? formatearCantidad(editando.value.existencia, editando.value.unidad)
    : formatearCantidad('0', formulario.unidad ?? ''),
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
  $q.notify({ type: 'positive', message: esEdicion ? 'Insumo actualizado' : 'Insumo registrado' });
};

onMounted(cargar);
</script>

<template>
  <q-page padding>
    <div class="row items-center justify-between q-mb-lg">
      <div>
        <div class="text-h4 titulo-pagina">Catálogo de insumos</div>
        <div class="text-body2 texto-suave">
          Administra los insumos con su código, costo unitario y stock mínimo.
        </div>
      </div>
      <q-btn
        unelevated
        color="primary"
        icon="add"
        label="Registrar insumo"
        :disable="cargando"
        @click="abrir()"
      />
    </div>

    <q-linear-progress v-if="cargando" indeterminate color="primary" />

    <div v-else-if="insumos.length === 0" class="texto-suave">Aún no hay insumos.</div>

    <div v-else class="row q-col-gutter-md">
      <div v-for="insumo in insumos" :key="insumo.id" class="col-12 col-sm-6 col-md-4 col-lg-3">
        <q-card flat bordered class="tarjeta full-height column">
          <q-card-section class="row items-start no-wrap">
            <div class="col">
              <div class="tarjeta__codigo">{{ insumo.codigo }}</div>
              <div class="tarjeta__nombre">{{ insumo.nombre }}</div>
            </div>
            <span v-if="!insumo.activo" class="insignia insignia--gris">Inactivo</span>
          </q-card-section>

          <q-card-section class="campos q-pt-none">
            <div class="campos__fila">
              <div class="campo">
                <div class="campo__etiqueta">Costo unitario</div>
                <div class="campo__valor campo__valor--mono">
                  {{ formatearCosto(insumo.costo_unitario) }} / {{ insumo.unidad }}
                </div>
              </div>
              <div class="campo">
                <div class="campo__etiqueta">Stock mínimo</div>
                <div class="campo__valor campo__valor--mono">
                  {{ formatearCantidad(insumo.stock_minimo, insumo.unidad) }}
                </div>
              </div>
            </div>
            <div class="campo">
              <div class="campo__etiqueta">Existencias actuales</div>
              <div class="campo__valor campo__valor--mono">
                {{ cantidades.format(Number(insumo.existencia)) }}
                <q-badge v-if="insumo.bajo_minimo" color="negative" class="q-ml-xs"
                  >Bajo mínimo</q-badge
                >
              </div>
            </div>
          </q-card-section>

          <q-space />
          <q-card-actions align="right">
            <q-btn
              class="btn-editar"
              outline
              no-caps
              color="dark"
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
            <div class="text-caption texto-suave">
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
              class="col-12"
              :class="editando ? 'col-sm-6' : 'col-sm-12'"
              :options="opcionesCategoria"
              label="Categoría"
              emit-value
              map-options
              outlined
              :error="campoError('categoria') !== ''"
              :error-message="campoError('categoria')"
            />
            <q-select
              v-if="editando"
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
              hint="Solo lectura: la calculan las entradas y salidas de inventario."
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

<style scoped lang="scss">
.titulo-pagina {
  font-family: $fuente-titulos;
  font-weight: 700;
  color: $dark;
}
.texto-suave {
  color: $grafito-medio;
}
.opcion-tipo {
  border: 1px solid $acero-claro;
}
.tarjeta {
  border-radius: 16px;
  border-color: $acero-claro;
}
.tarjeta__codigo {
  font-family: $fuente-codigo;
  font-size: 13px;
  letter-spacing: 0.04em;
  color: $grafito-medio;
}
.tarjeta__nombre {
  font-family: $fuente-titulos;
  font-size: 20px;
  font-weight: 700;
  line-height: 1.2;
  color: $dark;
}
.campos {
  display: flex;
  flex-direction: column;
  gap: 16px;
}
.campos__fila {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}
.campo {
  background: $blanco-humo;
  border-radius: 12px;
  padding: 12px 16px;
}
.campo__etiqueta {
  font-size: 13px;
  font-weight: 600;
  line-height: 1.3;
  color: $grafito-medio;
  margin-bottom: 4px;
}
.campo__valor {
  font-size: 16px;
  line-height: 1.4;
  color: $dark;
}
.campo__valor--mono {
  font-family: $fuente-codigo;
}
.insignia {
  display: inline-block;
  padding: 4px 12px;
  border-radius: 999px;
  font-size: 13px;
  font-weight: 600;
  line-height: 1.2;
  white-space: nowrap;
  color: #fff;
}
.insignia--azul {
  background: $info;
}
.insignia--verde {
  background: $positive;
}
.insignia--gris {
  background: $acero-claro;
  color: $grafito-medio;
}
.btn-editar {
  border-radius: 8px;
  font-weight: 500;
  transition: background-color $duracion-rapida $curva-salida;
}
</style>
