<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { useQuasar } from 'quasar';
import EstadoVacio from '@/components/comunes/EstadoVacio.vue';
import { formatearMoneda } from '@/composables/formato';
import type { Area } from '@aleacion-prima/types';
import { useApi } from '@/services/api';

interface FormularioArea {
  codigo: string;
  nombre: string;
  obligatoria: boolean | null;
  descripcion: string;
  tarifa_mano_obra_hora: number | null;
  costo_maquina_hora: number | null;
  orden_catalogo: number | null;
  encargados: number[];
  activo: boolean;
}

interface OpcionEncargado {
  id: number;
  name: string;
}

interface ErrorApi {
  message?: string;
  errors?: Record<string, string[]>;
}

const opcionesTipo = [
  { label: 'Obligatoria', caption: 'Requerida en el recorrido', value: true },
  { label: 'Opcional', caption: 'Se incluye cuando se necesita', value: false },
];

const $q = useQuasar();
const areas = ref<Area[]>([]);
const opciones = ref<OpcionEncargado[]>([]);
const dialogo = ref(false);
const editando = ref<Area | null>(null);
const cargando = ref(true);
const guardando = ref(false);
const errores = ref<Record<string, string[]>>({});
const formulario = reactive<FormularioArea>({
  codigo: '',
  nombre: '',
  obligatoria: null,
  descripcion: '',
  tarifa_mano_obra_hora: null,
  costo_maquina_hora: null,
  orden_catalogo: null,
  encargados: [],
  activo: true,
});

/** Un área obligatoria no puede estar inactiva: al elegirla, el estado pasa a activa. */
watch(
  () => formulario.obligatoria,
  (obligatoria) => {
    if (obligatoria === true) formulario.activo = true;
  },
);

/* ---------- Formato ---------- */

const encargadosLegible = (area: Area): string =>
  (area.encargados ?? []).map((encargado) => encargado.name).join(', ') || 'Sin encargados';

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
  const [areasRes, opcionesRes] = await Promise.all([
    useApi<Area[]>('/areas').json<Area[]>(),
    useApi<{ encargados: OpcionEncargado[] }>('/areas/opciones').json<{
      encargados: OpcionEncargado[];
    }>(),
  ]);
  cargando.value = false;

  if (areasRes.error.value) {
    $q.notify({ type: 'negative', message: 'No se pudieron cargar las áreas' });
    return;
  }
  areas.value = areasRes.data.value ?? [];
  opciones.value = opcionesRes.error.value ? [] : (opcionesRes.data.value?.encargados ?? []);
};

/* ---------- Encargados ---------- */

const opcionesEncargados = computed<OpcionEncargado[]>(() => {
  const lista = [...opciones.value];
  for (const actual of editando.value?.encargados ?? []) {
    if (!lista.some((opcion) => opcion.id === actual.id)) {
      lista.push(actual);
    }
  }
  return lista;
});

/* ---------- Diálogo ---------- */

const abrir = (area?: Area): void => {
  editando.value = area ?? null;
  errores.value = {};
  formulario.codigo = area?.codigo ?? '';
  formulario.nombre = area?.nombre ?? '';
  formulario.obligatoria = area ? area.obligatoria : null;
  formulario.descripcion = area?.descripcion ?? '';
  formulario.tarifa_mano_obra_hora = area ? Number(area.tarifa_mano_obra_hora) : null;
  formulario.costo_maquina_hora = area ? Number(area.costo_maquina_hora) : null;
  formulario.orden_catalogo =
    area?.orden_catalogo ?? Math.max(0, ...areas.value.map((a) => a.orden_catalogo)) + 1;
  formulario.encargados = (area?.encargados ?? []).map((encargado) => encargado.id);
  formulario.activo = area?.activo ?? true;
  dialogo.value = true;
};

const confirmarDesactivacion = (area: Area): Promise<boolean> =>
  new Promise((resolver) => {
    $q.dialog({
      title: `¿Desactivar el área de ${area.nombre}?`,
      message: 'Dejará de aparecer en recorridos nuevos. Los lotes que ya la usaron la conservan.',
      cancel: true,
      persistent: true,
    })
      .onOk(() => resolver(true))
      .onCancel(() => resolver(false))
      .onDismiss(() => resolver(false));
  });

const guardar = async (): Promise<void> => {
  const area = editando.value;
  // Una obligatoria siempre se guarda activa, aunque el estado venga desfasado.
  const activo = formulario.obligatoria === true ? true : formulario.activo;
  if (area?.activo && !activo && !(await confirmarDesactivacion(area))) return;

  const esEdicion = area !== null;
  const cuerpo = {
    codigo: formulario.codigo,
    nombre: formulario.nombre,
    descripcion: formulario.descripcion.trim() === '' ? null : formulario.descripcion,
    tarifa_mano_obra_hora: formulario.tarifa_mano_obra_hora,
    costo_maquina_hora: formulario.costo_maquina_hora,
    obligatoria: formulario.obligatoria,
    orden_catalogo: formulario.orden_catalogo,
    encargados: formulario.encargados,
    ...(esEdicion ? { activo } : {}),
  };

  guardando.value = true;
  errores.value = {};
  const { error, data } = esEdicion
    ? await useApi(`/areas/${area?.id ?? ''}`)
        .put(cuerpo)
        .json<Area>()
    : await useApi('/areas').post(cuerpo).json<Area>();
  guardando.value = false;

  if (error.value) {
    errores.value = (data.value as ErrorApi | null)?.errors ?? {};
    $q.notify({
      type: 'negative',
      message: textoError(data.value, 'No se pudo guardar el área'),
    });
    return;
  }
  dialogo.value = false;
  await cargar();
  $q.notify({ type: 'positive', message: esEdicion ? 'Área actualizada' : 'Área creada' });
};

onMounted(cargar);
</script>

<template>
  <q-page padding>
    <div class="row items-center justify-between q-mb-lg">
      <div>
        <div class="text-h4 catalogo-titulo">Áreas y tarifas</div>
        <div class="text-body2 text-secundario">
          Gestión de áreas, tarifas por hora y encargados asignados.
        </div>
      </div>
      <q-btn
        unelevated
        color="primary"
        icon="add"
        label="Nueva área"
        :disable="cargando"
        @click="abrir()"
      />
    </div>

    <q-linear-progress v-if="cargando" indeterminate color="primary" />

    <EstadoVacio v-else-if="areas.length === 0" titulo="Aún no hay áreas" />

    <div v-else class="row q-col-gutter-md">
      <div v-for="area in areas" :key="area.id" class="col-12 col-md-6">
        <q-card flat bordered class="catalogo-tarjeta full-height column">
          <q-card-section class="row items-start no-wrap">
            <div class="col">
              <div class="catalogo-codigo">{{ area.codigo }}</div>
              <div class="catalogo-nombre">{{ area.nombre }}</div>
            </div>
            <div class="chips row items-center no-wrap">
              <q-chip
                v-if="!area.activo"
                class="q-ma-none"
                color="grey-3"
                text-color="grey-8"
                label="Inactiva"
              />
              <q-chip
                v-if="area.obligatoria"
                class="q-ma-none"
                color="info"
                text-color="white"
                label="Obligatoria"
              />
              <q-chip
                v-else
                class="q-ma-none"
                color="grey-3"
                text-color="grey-8"
                label="Opcional"
              />
            </div>
          </q-card-section>

          <q-card-section class="catalogo-campos q-pt-none">
            <div class="catalogo-campo">
              <div class="catalogo-etiqueta">Descripción y proceso</div>
              <div class="catalogo-valor">{{ area.descripcion || '—' }}</div>
            </div>
            <div class="catalogo-fila">
              <div class="catalogo-campo">
                <div class="catalogo-etiqueta">Mano de obra ($/h)</div>
                <div class="catalogo-valor">
                  {{ formatearMoneda(area.tarifa_mano_obra_hora) }}
                </div>
              </div>
              <div class="catalogo-campo">
                <div class="catalogo-etiqueta">Máquina ($/h)</div>
                <div class="catalogo-valor">{{ formatearMoneda(area.costo_maquina_hora) }}</div>
              </div>
            </div>
            <div class="catalogo-campo">
              <div class="catalogo-etiqueta">Encargados asignados</div>
              <div class="catalogo-valor">{{ encargadosLegible(area) }}</div>
            </div>
          </q-card-section>

          <q-space />
          <q-card-actions align="right">
            <q-btn
              class="catalogo-boton"
              outline
              no-caps
              color="secondary"
              label="Editar área"
              @click="abrir(area)"
            />
          </q-card-actions>
        </q-card>
      </div>
    </div>

    <q-dialog v-model="dialogo" persistent>
      <q-card style="width: 640px; max-width: 95vw">
        <q-card-section class="row items-start no-wrap">
          <div class="col">
            <div class="text-h6">{{ editando ? 'Editar área' : 'Nueva área' }}</div>
            <div class="text-caption text-secundario">
              {{
                editando
                  ? 'Actualiza la información, las tarifas y los encargados del área.'
                  : 'Define el proceso, las tarifas por hora y los encargados del área.'
              }}
            </div>
          </div>
          <q-btn v-close-popup flat round dense icon="close" aria-label="Cerrar" />
        </q-card-section>
        <q-separator />

        <q-card-section class="scroll" style="max-height: 68vh">
          <div v-if="!editando" class="text-subtitle1 text-weight-bold q-mb-sm">
            Datos generales
          </div>
          <div class="row q-col-gutter-md">
            <q-input
              v-model="formulario.codigo"
              class="col-12 col-sm-3"
              :label="editando ? 'Código' : 'Código del área'"
              placeholder="Ingresa el código"
              stack-label
              maxlength="10"
              outlined
              :error="campoError('codigo') !== ''"
              :error-message="campoError('codigo')"
            />
            <q-input
              v-model="formulario.nombre"
              class="col-12 col-sm-6"
              label="Nombre del área"
              placeholder="Ingresa el nombre del área"
              stack-label
              outlined
              :error="campoError('nombre') !== ''"
              :error-message="campoError('nombre')"
            />
            <q-input
              v-model.number="formulario.orden_catalogo"
              class="col-12 col-sm-3"
              label="Orden en el catálogo"
              stack-label
              type="number"
              min="1"
              outlined
              :error="campoError('orden_catalogo') !== ''"
              :error-message="campoError('orden_catalogo')"
            />

            <q-select
              v-if="editando"
              v-model="formulario.obligatoria"
              class="col-12"
              :options="opcionesTipo"
              label="Tipo"
              emit-value
              map-options
              outlined
              :error="campoError('obligatoria') !== ''"
              :error-message="campoError('obligatoria')"
            />
            <div v-else class="col-12">
              <div class="text-caption text-secundario q-mb-xs">Tipo de área</div>
              <div class="row q-col-gutter-md">
                <div v-for="opcion in opcionesTipo" :key="opcion.label" class="col-12 col-sm-6">
                  <q-item v-ripple tag="label" class="opcion-tipo rounded-borders">
                    <q-item-section avatar>
                      <q-radio v-model="formulario.obligatoria" :val="opcion.value" dense />
                    </q-item-section>
                    <q-item-section>
                      <q-item-label>{{ opcion.label }}</q-item-label>
                      <q-item-label caption>{{ opcion.caption }}</q-item-label>
                    </q-item-section>
                  </q-item>
                </div>
              </div>
              <div
                v-if="campoError('obligatoria') !== ''"
                class="text-negative text-caption q-mt-xs"
              >
                {{ campoError('obligatoria') }}
              </div>
            </div>

            <div v-if="editando" class="col-12">
              <div class="estado-area row items-center justify-between no-wrap rounded-borders">
                <div>
                  <div class="estado-area__etiqueta">Estado</div>
                  <div class="estado-area__valor">
                    {{ formulario.activo ? 'Activa' : 'Inactiva' }}
                  </div>
                </div>
                <q-toggle
                  v-model="formulario.activo"
                  color="positive"
                  aria-label="Estado del área"
                  :disable="formulario.obligatoria === true"
                />
              </div>
              <div v-if="campoError('activo') !== ''" class="text-negative text-caption q-mt-xs">
                {{ campoError('activo') }}
              </div>
            </div>

            <q-input
              v-model="formulario.descripcion"
              class="col-12"
              label="Descripción y proceso"
              placeholder="Describe las actividades y el proceso del área"
              stack-label
              type="textarea"
              autogrow
              outlined
              :error="campoError('descripcion') !== ''"
              :error-message="campoError('descripcion')"
            />
          </div>

          <q-separator class="q-my-lg" />

          <div class="text-subtitle1 text-weight-bold">Tarifas por hora</div>
          <div v-if="!editando" class="text-caption text-secundario">
            Ingrese el importe por hora para cada recurso.
          </div>
          <div class="row q-col-gutter-md">
            <q-input
              v-model.number="formulario.tarifa_mano_obra_hora"
              class="col-12 col-sm-6"
              label="Mano de obra ($/h)"
              placeholder="Ingresa el importe"
              stack-label
              type="number"
              min="0"
              step="any"
              prefix="$"
              outlined
              :error="campoError('tarifa_mano_obra_hora') !== ''"
              :error-message="campoError('tarifa_mano_obra_hora')"
            />
            <q-input
              v-model.number="formulario.costo_maquina_hora"
              class="col-12 col-sm-6"
              label="Máquina ($/h)"
              placeholder="Ingresa el importe"
              stack-label
              type="number"
              min="0"
              step="any"
              prefix="$"
              outlined
              :error="campoError('costo_maquina_hora') !== ''"
              :error-message="campoError('costo_maquina_hora')"
            />
            <q-select
              v-model="formulario.encargados"
              class="col-12"
              :options="opcionesEncargados"
              option-value="id"
              option-label="name"
              label="Encargados asignados"
              placeholder="Selecciona uno o más encargados"
              stack-label
              :hint="editando ? undefined : 'Selecciona al personal responsable de esta área.'"
              multiple
              use-chips
              emit-value
              map-options
              outlined
              :error="campoError('encargados') !== '' || campoError('encargados.0') !== ''"
              :error-message="campoError('encargados') || campoError('encargados.0')"
            />
          </div>
        </q-card-section>

        <q-separator />
        <q-card-actions align="right">
          <q-btn v-close-popup outline color="primary" label="Cancelar" :disable="guardando" />
          <q-btn
            color="primary"
            unelevated
            :label="editando ? 'Guardar cambios' : 'Registrar área'"
            :loading="guardando"
            @click="guardar"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<style scoped lang="scss">
.chips {
  gap: 8px;
}
.opcion-tipo {
  border: 1px solid $acero-claro;
}
.estado-area {
  border: 1px solid $acero-claro;
  padding: 8px 16px;
}
.estado-area__etiqueta {
  font-size: 13px;
  font-weight: 600;
  line-height: 1.3;
  color: $grafito-medio;
}
.estado-area__valor {
  font-size: 16px;
  line-height: 1.4;
  color: $dark;
}
</style>
