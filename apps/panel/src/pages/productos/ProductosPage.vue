<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue';
import { useQuasar } from 'quasar';
import type { AreaOpcion, OpcionesProducto, Producto } from '@aleacion-prima/types';
import { useApi } from '@/services/api';

interface OpcionMaterial {
  label: string;
  material: string;
  norma: string | null;
}

interface RenglonReceta {
  insumo_id: number | null;
  cantidad_por_pieza: number | null;
}

interface FormularioProducto {
  codigo: string;
  nombre: string;
  material: string | null;
  precio_mayoreo: number | null;
  precio_menudeo: number | null;
  activo: boolean | null;
  /** Ids de área en el orden del recorrido. */
  recorrido: number[];
  receta: RenglonReceta[];
}

/** Respuesta 422 de Laravel. */
interface ErrorApi {
  message?: string;
  errors?: Record<string, string[]>;
}

/** Materiales de docs/empresa.md (sección 4). Se guardan en `especificaciones.material` y `.norma`. */
const MATERIALES = [
  { material: 'Hierro gris clase 30', norma: 'ASTM A48' },
  { material: 'Hierro nodular 65-45-12', norma: 'ASTM A536' },
  { material: 'Hierro nodular 80-55-06', norma: 'ASTM A536' },
];

const opcionesEstado = [
  { label: 'Activo', value: true },
  { label: 'Inactivo', value: false },
];

const $q = useQuasar();
const productos = ref<Producto[]>([]);
const opciones = ref<OpcionesProducto>({ areas: [], insumos: [] });
const dialogo = ref(false);
const editando = ref<Producto | null>(null);
const cargando = ref(true);
const guardando = ref(false);
const errores = ref<Record<string, string[]>>({});
const arrastrando = ref<number | null>(null);
const formulario = reactive<FormularioProducto>({
  codigo: '',
  nombre: '',
  material: null,
  precio_mayoreo: null,
  precio_menudeo: null,
  activo: null,
  recorrido: [],
  receta: [],
});

/* ---------- Formato ---------- */

const dinero = new Intl.NumberFormat('es-MX', {
  style: 'currency',
  currency: 'MXN',
  minimumFractionDigits: 0,
  maximumFractionDigits: 2,
});
const cantidades = new Intl.NumberFormat('es-MX', { maximumFractionDigits: 4 });

const formatearPrecio = (valor: string): string => dinero.format(Number(valor));

const materialLegible = (producto: Producto): string => {
  const { material, norma } = producto.especificaciones;
  if (material === undefined) return '—';
  return norma === undefined ? String(material) : `${material} (${norma})`;
};

const recorridoLegible = (producto: Producto): string =>
  producto.recorrido.map((area) => area.codigo).join(' → ');

/** Los dos primeros insumos y cuántos más hay. */
const resumenReceta = (producto: Producto): string => {
  if (producto.receta.length === 0) return '—';
  const visibles = producto.receta
    .slice(0, 2)
    .map(
      (renglon) =>
        `${renglon.nombre} ${cantidades.format(Number(renglon.cantidad_por_pieza))} ${renglon.unidad}`,
    );
  const restantes = producto.receta.length - visibles.length;
  return [...visibles, ...(restantes > 0 ? [`+${restantes} más`] : [])].join(' · ');
};

/* ---------- Errores de la API ---------- */

const textoError = (cuerpo: unknown, porDefecto: string): string => {
  const detalle = cuerpo as ErrorApi | null;
  const primero = Object.values(detalle?.errors ?? {})[0]?.[0];
  return primero ?? detalle?.message ?? porDefecto;
};

const campoError = (campo: string): string => errores.value[campo]?.[0] ?? '';

/** Errores del campo y de sus renglones (`recorrido`, `recorrido.2`, ...). */
const erroresDe = (campo: string): string[] =>
  Object.entries(errores.value)
    .filter(([llave]) => llave === campo || llave.startsWith(`${campo}.`))
    .map(([, mensajes]) => mensajes[0] ?? '')
    .filter((mensaje) => mensaje !== '');

/* ---------- Carga ---------- */

const cargar = async (): Promise<void> => {
  cargando.value = true;
  const [productosRes, opcionesRes] = await Promise.all([
    useApi<Producto[]>('/productos').json<Producto[]>(),
    useApi<OpcionesProducto>('/productos/opciones').json<OpcionesProducto>(),
  ]);
  cargando.value = false;

  if (productosRes.error.value || opcionesRes.error.value) {
    $q.notify({ type: 'negative', message: 'No se pudieron cargar los productos' });
    return;
  }

  productos.value = productosRes.data.value ?? [];
  opciones.value = opcionesRes.data.value ?? { areas: [], insumos: [] };
};

/* ---------- Material ---------- */

const opcionesMaterial = computed<OpcionMaterial[]>(() => {
  const lista: OpcionMaterial[] = MATERIALES.map((item) => ({
    label: `${item.material} (${item.norma})`,
    ...item,
  }));
  const actual = formulario.material;
  // Un producto con un material fuera de la lista no lo pierde al editarlo.
  if (actual !== null && !lista.some((item) => item.material === actual)) {
    lista.push({ label: actual, material: actual, norma: null });
  }
  return lista;
});

/** Parte de las especificaciones que ya tiene el producto: esta pantalla solo edita material y norma. */
const especificacionesParaApi = (): Record<string, string | number> => {
  const especificaciones = { ...(editando.value?.especificaciones ?? {}) };
  const elegido = opcionesMaterial.value.find((item) => item.material === formulario.material);
  if (elegido) {
    especificaciones.material = elegido.material;
    if (elegido.norma) especificaciones.norma = elegido.norma;
  }
  return especificaciones;
};

/* ---------- Recorrido ---------- */

const areaPorId = (id: number): Pick<AreaOpcion, 'codigo' | 'nombre' | 'obligatoria'> | undefined =>
  opciones.value.areas.find((area) => area.id === id) ??
  editando.value?.recorrido.find((area) => area.id === id);

const areasParaAgregar = computed(() =>
  opciones.value.areas.filter((area) => !formulario.recorrido.includes(area.id)),
);

const agregarArea = (id: number): void => {
  formulario.recorrido.push(id);
};

const quitarArea = (indice: number): void => {
  formulario.recorrido.splice(indice, 1);
};

const iniciarArrastre = (indice: number, evento: DragEvent): void => {
  arrastrando.value = indice;
  if (evento.dataTransfer) {
    evento.dataTransfer.effectAllowed = 'move';
    evento.dataTransfer.setData('text/plain', String(indice));
  }
};

const soltarSobre = (destino: number): void => {
  const origen = arrastrando.value;
  arrastrando.value = null;
  if (origen === null || origen === destino) return;
  const [id] = formulario.recorrido.splice(origen, 1);
  if (id !== undefined) formulario.recorrido.splice(destino, 0, id);
};

/* ---------- Receta ---------- */

const insumosDisponibles = (indice: number) =>
  opciones.value.insumos.filter(
    (insumo) =>
      insumo.id === formulario.receta[indice]?.insumo_id ||
      !formulario.receta.some((renglon) => renglon.insumo_id === insumo.id),
  );

/** La unidad es del insumo (`insumos.unidad`), no del renglón de la receta. */
const unidadDe = (insumoId: number | null): string =>
  opciones.value.insumos.find((insumo) => insumo.id === insumoId)?.unidad ?? '';

const agregarRenglonReceta = (): void => {
  formulario.receta.push({ insumo_id: null, cantidad_por_pieza: null });
};

const recetaVacia = computed(() =>
  formulario.receta.every((renglon) => renglon.insumo_id === null),
);

/* ---------- Diálogo ---------- */

const abrir = (producto?: Producto): void => {
  editando.value = producto ?? null;
  errores.value = {};
  const material = producto?.especificaciones.material;
  formulario.codigo = producto?.codigo ?? '';
  formulario.nombre = producto?.nombre ?? '';
  formulario.material = material === undefined ? null : String(material);
  formulario.precio_mayoreo = producto ? Number(producto.precio_mayoreo) : null;
  formulario.precio_menudeo = producto ? Number(producto.precio_menudeo) : null;
  formulario.activo = producto ? producto.activo : null;
  // Producto nuevo: arranca con las áreas obligatorias, ya en su orden de catálogo.
  formulario.recorrido = producto
    ? [...producto.recorrido].sort((a, b) => a.orden - b.orden).map((area) => area.id)
    : opciones.value.areas.filter((area) => area.obligatoria).map((area) => area.id);
  formulario.receta = producto
    ? producto.receta.map((renglon) => ({
        insumo_id: renglon.insumo_id,
        cantidad_por_pieza: Number(renglon.cantidad_por_pieza),
      }))
    : [{ insumo_id: null, cantidad_por_pieza: null }];
  dialogo.value = true;
};

const guardar = async (): Promise<void> => {
  const esEdicion = editando.value !== null;
  const cuerpo = {
    codigo: formulario.codigo,
    nombre: formulario.nombre,
    especificaciones: especificacionesParaApi(),
    precio_mayoreo: formulario.precio_mayoreo,
    precio_menudeo: formulario.precio_menudeo,
    recorrido: formulario.recorrido,
    receta: formulario.receta,
    // Sin estado elegido, el alta lo toma como activo.
    ...(formulario.activo !== null ? { activo: formulario.activo } : {}),
  };

  guardando.value = true;
  errores.value = {};
  const { error, data } = esEdicion
    ? await useApi(`/productos/${editando.value?.id ?? ''}`)
        .put(cuerpo)
        .json<Producto>()
    : await useApi('/productos').post(cuerpo).json<Producto>();
  guardando.value = false;

  if (error.value) {
    errores.value = (data.value as ErrorApi | null)?.errors ?? {};
    $q.notify({
      type: 'negative',
      message: textoError(data.value, 'No se pudo guardar el producto'),
    });
    return;
  }
  dialogo.value = false;
  await cargar();
  $q.notify({ type: 'positive', message: esEdicion ? 'Producto actualizado' : 'Producto creado' });
};

onMounted(cargar);
</script>

<template>
  <q-page padding>
    <div class="row items-center justify-between q-mb-lg">
      <div>
        <div class="text-h4">Productos</div>
        <div class="text-body2 text-grey-7">
          Administra los productos, su recorrido por defecto y la receta de insumos.
        </div>
      </div>
      <q-btn
        outline
        color="primary"
        icon="add"
        label="Nuevo producto"
        :disable="cargando"
        @click="abrir()"
      />
    </div>

    <q-linear-progress v-if="cargando" indeterminate color="primary" />

    <div v-else-if="productos.length === 0" class="text-grey-7">Aún no hay productos.</div>

    <div v-else class="row q-col-gutter-md">
      <div v-for="producto in productos" :key="producto.id" class="col-12 col-sm-6 col-lg-4">
        <q-card flat bordered class="full-height column">
          <q-card-section class="row items-start no-wrap">
            <div class="col">
              <div class="text-caption text-grey-7">{{ producto.codigo }}</div>
              <div class="text-h6">{{ producto.nombre }}</div>
            </div>
            <q-badge :color="producto.activo ? 'positive' : 'grey'">{{
              producto.activo ? 'Activo' : 'Inactivo'
            }}</q-badge>
          </q-card-section>

          <q-card-section class="column q-gutter-sm q-pt-none">
            <div class="bg-grey-2 rounded-borders q-pa-sm row">
              <div class="col-6">
                <div class="text-caption text-grey-7">Material</div>
                <div>{{ materialLegible(producto) }}</div>
              </div>
              <div class="col-6">
                <div class="text-caption text-grey-7">Precio de mayoreo / menudeo</div>
                <div>
                  {{ formatearPrecio(producto.precio_mayoreo) }} /
                  {{ formatearPrecio(producto.precio_menudeo) }}
                </div>
              </div>
            </div>
            <div class="bg-grey-2 rounded-borders q-pa-sm">
              <div class="text-caption text-grey-7">Recorrido por defecto</div>
              <div>{{ recorridoLegible(producto) }}</div>
            </div>
            <div class="bg-grey-2 rounded-borders q-pa-sm">
              <div class="text-caption text-grey-7">Receta de insumos (por pieza)</div>
              <div>{{ resumenReceta(producto) }}</div>
            </div>
          </q-card-section>

          <q-space />
          <q-card-actions align="right">
            <q-btn outline color="primary" label="Editar producto" @click="abrir(producto)" />
          </q-card-actions>
        </q-card>
      </div>
    </div>

    <q-dialog v-model="dialogo" persistent>
      <q-card style="width: 820px; max-width: 95vw">
        <q-card-section class="row items-start no-wrap">
          <div class="col">
            <div class="text-h6">{{ editando ? 'Editar producto' : 'Nuevo producto' }}</div>
            <div class="text-caption text-grey-7">
              {{
                editando
                  ? 'Actualiza los datos, el recorrido y la receta del producto.'
                  : 'Completa los datos y establece el recorrido y la receta del producto.'
              }}
            </div>
          </div>
          <q-btn v-close-popup flat round dense icon="close" aria-label="Cerrar" />
        </q-card-section>
        <q-separator />

        <q-card-section class="scroll" style="max-height: 68vh">
          <!-- Datos del producto -->
          <div>
            <div class="text-subtitle1 text-weight-bold q-mb-sm">Datos del producto</div>
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
                class="col-12 col-sm-5"
                label="Nombre del producto"
                outlined
                :error="campoError('nombre') !== ''"
                :error-message="campoError('nombre')"
              />
              <q-select
                v-model="formulario.activo"
                class="col-12 col-sm-3"
                :options="opcionesEstado"
                label="Estado"
                emit-value
                map-options
                outlined
                :hint="editando ? undefined : 'Opciones: Activo · Inactivo'"
                :error="campoError('activo') !== ''"
                :error-message="campoError('activo')"
              />
              <q-select
                v-model="formulario.material"
                class="col-12 col-sm-6"
                :options="opcionesMaterial"
                option-value="material"
                option-label="label"
                label="Material"
                emit-value
                map-options
                outlined
                :error="campoError('especificaciones.material') !== ''"
                :error-message="campoError('especificaciones.material')"
              />
              <q-input
                v-model.number="formulario.precio_mayoreo"
                class="col-12 col-sm-3"
                label="Precio de mayoreo"
                type="number"
                step="any"
                prefix="$"
                outlined
                :error="campoError('precio_mayoreo') !== ''"
                :error-message="campoError('precio_mayoreo')"
              />
              <q-input
                v-model.number="formulario.precio_menudeo"
                class="col-12 col-sm-3"
                label="Precio de menudeo"
                type="number"
                step="any"
                prefix="$"
                outlined
                :error="campoError('precio_menudeo') !== ''"
                :error-message="campoError('precio_menudeo')"
              />
            </div>
          </div>
          <q-separator class="q-my-lg" />

          <!-- Recorrido por defecto -->
          <div>
            <div class="row items-start justify-between q-mb-sm">
              <div>
                <div class="text-subtitle1 text-weight-bold">Recorrido por defecto</div>
                <div class="text-caption text-grey-7">
                  Agrega áreas y arrástralas para ajustar la secuencia. Las obligatorias no se
                  pueden quitar.
                </div>
              </div>
              <q-btn
                outline
                color="primary"
                icon="add"
                label="Agregar área"
                :disable="areasParaAgregar.length === 0"
              >
                <q-menu>
                  <q-list style="min-width: 240px">
                    <q-item
                      v-for="area in areasParaAgregar"
                      :key="area.id"
                      v-close-popup
                      clickable
                      @click="agregarArea(area.id)"
                    >
                      <q-item-section>{{ area.codigo }} · {{ area.nombre }}</q-item-section>
                    </q-item>
                  </q-list>
                </q-menu>
              </q-btn>
            </div>

            <div class="row items-center bg-grey-2 rounded-borders q-pa-sm" style="gap: 4px">
              <div v-if="formulario.recorrido.length === 0" class="text-grey-7">
                <q-icon name="drag_indicator" /> Sin áreas agregadas
              </div>
              <div
                v-for="(areaId, indice) in formulario.recorrido"
                :key="areaId"
                class="row items-center no-wrap"
              >
                <div
                  draggable="true"
                  style="cursor: grab"
                  @dragstart="iniciarArrastre(indice, $event)"
                  @dragover.prevent
                  @drop.prevent="soltarSobre(indice)"
                  @dragend="arrastrando = null"
                >
                  <q-chip
                    square
                    color="white"
                    icon="drag_indicator"
                    :removable="areaPorId(areaId)?.obligatoria !== true"
                    @remove="quitarArea(indice)"
                  >
                    {{ areaPorId(areaId)?.codigo }}
                  </q-chip>
                </div>
                <q-icon
                  v-if="indice < formulario.recorrido.length - 1"
                  name="chevron_right"
                  color="grey-6"
                />
              </div>
            </div>
            <div
              v-for="mensaje in erroresDe('recorrido')"
              :key="mensaje"
              class="text-negative text-caption q-mt-xs"
            >
              {{ mensaje }}
            </div>
          </div>
          <q-separator class="q-my-lg" />

          <!-- Receta de insumos -->
          <div>
            <div class="row items-start justify-between q-mb-sm">
              <div>
                <div class="text-subtitle1 text-weight-bold">Receta de insumos por pieza</div>
                <div class="text-caption text-grey-7">
                  Define el insumo y la cantidad de cada pieza; la unidad viene del insumo.
                </div>
              </div>
              <q-btn
                outline
                color="primary"
                icon="add"
                label="Agregar insumo"
                @click="agregarRenglonReceta"
              />
            </div>

            <div
              v-for="(renglon, indice) in formulario.receta"
              :key="indice"
              class="q-mb-sm"
              style="
                display: grid;
                grid-template-columns: minmax(0, 1fr) 130px 100px auto;
                gap: 8px;
                align-items: start;
              "
            >
              <q-select
                v-model="renglon.insumo_id"
                :options="insumosDisponibles(indice)"
                :option-label="(insumo) => `${insumo.codigo} · ${insumo.nombre}`"
                option-value="id"
                label="Selecciona un insumo"
                emit-value
                map-options
                outlined
                :error="campoError(`receta.${indice}.insumo_id`) !== ''"
                :error-message="campoError(`receta.${indice}.insumo_id`)"
              />
              <q-input
                v-model.number="renglon.cantidad_por_pieza"
                label="Cantidad"
                type="number"
                step="any"
                outlined
                :error="campoError(`receta.${indice}.cantidad_por_pieza`) !== ''"
                :error-message="campoError(`receta.${indice}.cantidad_por_pieza`)"
              />
              <q-input
                :model-value="unidadDe(renglon.insumo_id)"
                label="Unidad"
                readonly
                outlined
              />
              <q-btn
                flat
                round
                class="q-mt-xs"
                icon="close"
                aria-label="Quitar insumo"
                @click="formulario.receta.splice(indice, 1)"
              />
            </div>

            <div v-if="recetaVacia" class="bg-grey-2 rounded-borders q-pa-md text-grey-7">
              Aún no hay insumos agregados. Completa los campos para establecer la receta.
            </div>
            <div v-if="campoError('receta') !== ''" class="text-negative text-caption">
              {{ campoError('receta') }}
            </div>
          </div>
        </q-card-section>

        <q-separator />
        <q-card-actions align="right">
          <q-btn v-close-popup outline color="primary" label="Cancelar" />
          <q-btn
            color="primary"
            unelevated
            :label="editando ? 'Guardar cambios' : 'Registrar producto'"
            :loading="guardando"
            @click="guardar"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>
