<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue';
import { useQuasar } from 'quasar';
import type { Area } from '@aleacion-prima/types';
import { useApi } from '@/services/api';

interface FormularioArea {
  codigo: string;
  nombre: string;
  obligatoria: boolean | null;
  descripcion: string;
  tarifa_mano_obra_hora: number | null;
  costo_maquina_hora: number | null;
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
  encargados: [],
  activo: true,
});

/* ---------- Formato ---------- */

const dinero = new Intl.NumberFormat('es-MX', {
  style: 'currency',
  currency: 'MXN',
  minimumFractionDigits: 0,
  maximumFractionDigits: 2,
});

const formatearTarifa = (valor: string): string => dinero.format(Number(valor));

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
  formulario.encargados = (area?.encargados ?? []).map((encargado) => encargado.id);
  formulario.activo = area?.activo ?? true;
  dialogo.value = true;
};

const guardar = async (): Promise<void> => {
  const esEdicion = editando.value !== null;
  const cuerpo = {
    codigo: formulario.codigo,
    nombre: formulario.nombre,
    descripcion: formulario.descripcion.trim() === '' ? null : formulario.descripcion,
    tarifa_mano_obra_hora: formulario.tarifa_mano_obra_hora,
    costo_maquina_hora: formulario.costo_maquina_hora,
    obligatoria: formulario.obligatoria,
    encargados: formulario.encargados,
    ...(esEdicion ? { activo: formulario.activo } : {}),
  };

  guardando.value = true;
  errores.value = {};
  const { error, data } = esEdicion
    ? await useApi(`/areas/${editando.value?.id ?? ''}`)
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
        <div class="text-h4 titulo-pagina">Áreas y tarifas</div>
        <div class="text-body2 texto-suave">
          Gestión de áreas, tarifas por hora y encargados asignados.
        </div>
      </div>
      <q-btn
        outline
        color="primary"
        icon="add"
        label="Nueva área"
        :disable="cargando"
        @click="abrir()"
      />
    </div>

    <q-linear-progress v-if="cargando" indeterminate color="primary" />

    <div v-else-if="areas.length === 0" class="texto-suave">Aún no hay áreas.</div>

    <div v-else class="row q-col-gutter-md">
      <div v-for="area in areas" :key="area.id" class="col-12 col-md-6">
        <q-card flat bordered class="tarjeta full-height column">
          <q-card-section class="row items-start no-wrap">
            <div class="col">
              <div class="tarjeta__codigo">{{ area.codigo }}</div>
              <div class="tarjeta__nombre">{{ area.nombre }}</div>
            </div>
            <div class="row items-center no-wrap q-gutter-xs">
              <span v-if="!area.activo" class="insignia insignia--gris">Inactiva</span>
              <span v-if="area.obligatoria" class="insignia insignia--azul">Obligatoria</span>
              <span v-else class="insignia insignia--gris">Opcional</span>
            </div>
          </q-card-section>

          <q-card-section class="campos q-pt-none">
            <div class="campo">
              <div class="campo__etiqueta">Descripción y proceso</div>
              <div class="campo__valor">{{ area.descripcion || '—' }}</div>
            </div>
            <div class="campos__fila">
              <div class="campo">
                <div class="campo__etiqueta">Mano de obra ($/h)</div>
                <div class="campo__valor">{{ formatearTarifa(area.tarifa_mano_obra_hora) }}</div>
              </div>
              <div class="campo">
                <div class="campo__etiqueta">Máquina ($/h)</div>
                <div class="campo__valor">{{ formatearTarifa(area.costo_maquina_hora) }}</div>
              </div>
            </div>
            <div class="campo">
              <div class="campo__etiqueta">Encargados asignados</div>
              <div class="campo__valor">{{ encargadosLegible(area) }}</div>
            </div>
          </q-card-section>

          <q-space />
          <q-card-actions align="right">
            <q-btn
              class="btn-editar"
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
            <div class="text-caption texto-suave">
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
              class="col-12 col-sm-4"
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
              class="col-12 col-sm-8"
              label="Nombre del área"
              placeholder="Ingresa el nombre del área"
              stack-label
              outlined
              :error="campoError('nombre') !== ''"
              :error-message="campoError('nombre')"
            />

            <!-- Edición: lista desplegable; alta: dos opciones en tarjeta -->
            <q-select
              v-if="editando"
              v-model="formulario.obligatoria"
              class="col-12"
              :options="opcionesTipo"
              label="Tipo"
              hint="Selecciona Obligatoria u Opcional."
              emit-value
              map-options
              outlined
              :error="campoError('obligatoria') !== ''"
              :error-message="campoError('obligatoria')"
            />
            <div v-else class="col-12">
              <div class="text-caption texto-suave q-mb-xs">Tipo de área</div>
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
          <div v-if="!editando" class="text-caption texto-suave">
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
              stack-label
              :hint="editando ? undefined : 'Selecciona al personal responsable de esta área.'"
              multiple
              use-chips
              emit-value
              map-options
              outlined
              :error="campoError('encargados') !== ''"
              :error-message="campoError('encargados')"
            />
            <div v-if="editando" class="col-12">
              <q-toggle
                v-model="formulario.activo"
                label="Área activa"
                :disable="formulario.obligatoria === true"
              />
              <div v-if="campoError('activo') !== ''" class="text-negative text-caption">
                {{ campoError('activo') }}
              </div>
            </div>
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
