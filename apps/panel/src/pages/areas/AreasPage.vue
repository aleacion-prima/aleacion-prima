<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue';
import { useQuasar, type QTableColumn } from 'quasar';
import type { Area } from '@aleacion-prima/types';
import { API_URL, cabecerasApi } from '@/services/api';

interface FormularioArea {
  codigo: string;
  nombre: string;
  descripcion: string;
  tarifa_mano_obra_hora: string;
  costo_maquina_hora: string;
  obligatoria: boolean;
  orden_catalogo: string;
  activo: boolean;
}

const $q = useQuasar();
const areas = ref<Area[]>([]);
const verInactivas = ref(false);
const dialogo = ref(false);
const editando = ref<Area | null>(null);
const cargando = ref(true);
const guardando = ref(false);
const formulario = reactive<FormularioArea>({
  codigo: '',
  nombre: '',
  descripcion: '',
  tarifa_mano_obra_hora: '',
  costo_maquina_hora: '',
  obligatoria: false,
  orden_catalogo: '',
  activo: true,
});

const moneda = (valor: string): string =>
  Number(valor).toLocaleString('es-MX', { style: 'currency', currency: 'MXN' });

const columnas: QTableColumn<Area>[] = [
  { name: 'orden_catalogo', label: '#', field: 'orden_catalogo', align: 'left' },
  { name: 'codigo', label: 'Código', field: 'codigo', align: 'left' },
  { name: 'nombre', label: 'Área', field: 'nombre', align: 'left' },
  {
    name: 'tarifa_mano_obra_hora',
    label: 'Mano de obra ($/h)',
    field: (fila) => moneda(fila.tarifa_mano_obra_hora),
    align: 'right',
  },
  {
    name: 'costo_maquina_hora',
    label: 'Máquina ($/h)',
    field: (fila) => moneda(fila.costo_maquina_hora),
    align: 'right',
  },
  { name: 'obligatoria', label: 'Recorrido', field: 'obligatoria', align: 'left' },
  { name: 'activo', label: 'Estado', field: 'activo', align: 'left' },
  { name: 'acciones', label: '', field: 'id', align: 'right' },
];

const filas = computed(() => areas.value.filter((area) => verInactivas.value || area.activo));

/** Primer mensaje de validación (422) o el mensaje general de la API. */
const mensajeDeError = async (res: Response, porDefecto: string): Promise<string> => {
  try {
    const cuerpo = (await res.json()) as { message?: string; errors?: Record<string, string[]> };
    return Object.values(cuerpo.errors ?? {})[0]?.[0] ?? cuerpo.message ?? porDefecto;
  } catch {
    return porDefecto;
  }
};

const cargar = async (): Promise<void> => {
  cargando.value = true;
  try {
    const res = await fetch(`${API_URL}/api/areas`, {
      credentials: 'include',
      headers: cabecerasApi(),
    });
    if (!res.ok) throw new Error('No se pudieron cargar las áreas');
    areas.value = (await res.json()) as Area[];
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: error instanceof Error ? error.message : 'Error de red',
    });
  } finally {
    cargando.value = false;
  }
};

const abrir = (area?: Area): void => {
  editando.value = area ?? null;
  formulario.codigo = area?.codigo ?? '';
  formulario.nombre = area?.nombre ?? '';
  formulario.descripcion = area?.descripcion ?? '';
  formulario.tarifa_mano_obra_hora = area ? String(Number(area.tarifa_mano_obra_hora)) : '';
  formulario.costo_maquina_hora = area ? String(Number(area.costo_maquina_hora)) : '';
  formulario.obligatoria = area?.obligatoria ?? false;
  formulario.orden_catalogo = String(
    area?.orden_catalogo ?? Math.max(0, ...areas.value.map((a) => a.orden_catalogo)) + 1,
  );
  formulario.activo = area?.activo ?? true;
  dialogo.value = true;
};

const guardar = async (): Promise<void> => {
  const esEdicion = editando.value !== null;
  const url = esEdicion
    ? `${API_URL}/api/areas/${editando.value?.id ?? ''}`
    : `${API_URL}/api/areas`;
  const { activo, ...datos } = formulario;
  const cuerpo = esEdicion ? { ...datos, activo } : datos;

  guardando.value = true;
  try {
    const res = await fetch(url, {
      method: esEdicion ? 'PUT' : 'POST',
      credentials: 'include',
      headers: cabecerasApi({ 'content-type': 'application/json' }),
      body: JSON.stringify(cuerpo),
    });
    if (!res.ok) {
      $q.notify({
        type: 'negative',
        message: await mensajeDeError(res, 'No se pudo guardar el área'),
      });
      return;
    }
    dialogo.value = false;
    await cargar();
    $q.notify({ type: 'positive', message: esEdicion ? 'Área actualizada' : 'Área creada' });
  } finally {
    guardando.value = false;
  }
};

const desactivarConfirmado = async (area: Area): Promise<void> => {
  const res = await fetch(`${API_URL}/api/areas/${area.id}`, {
    method: 'DELETE',
    credentials: 'include',
    headers: cabecerasApi(),
  });
  if (!res.ok) {
    $q.notify({
      type: 'negative',
      message: await mensajeDeError(res, 'No se pudo desactivar el área'),
    });
    return;
  }
  await cargar();
  $q.notify({ type: 'positive', message: 'Área desactivada' });
};

const desactivar = (area: Area): void => {
  $q.dialog({
    title: 'Desactivar área',
    message: `¿Desactivar ${area.codigo} · ${area.nombre}? Dejará de aparecer en recorridos nuevos; los lotes que ya la usaron la conservan.`,
    cancel: true,
    persistent: true,
  }).onOk(() => {
    void desactivarConfirmado(area);
  });
};

onMounted(cargar);
</script>

<template>
  <q-page padding>
    <div class="row items-center justify-between q-mb-md">
      <div class="text-h4">Áreas y tarifas</div>
      <div class="row items-center q-gutter-md">
        <q-toggle v-model="verInactivas" label="Ver inactivas" />
        <q-btn color="primary" unelevated icon="add" label="Nueva área" @click="abrir()" />
      </div>
    </div>

    <q-banner dense rounded class="bg-blue-1 text-blue-10 q-mb-lg">
      Cambiar una tarifa no modifica los lotes existentes: cada lote conserva las tarifas con las
      que se creó.
    </q-banner>

    <q-table
      flat
      bordered
      :rows="filas"
      :columns="columnas"
      row-key="id"
      :loading="cargando"
      :pagination="{ rowsPerPage: 0 }"
      hide-pagination
      no-data-label="No hay áreas que mostrar"
    >
      <template #body-cell-obligatoria="{ row }">
        <q-td
          ><q-badge :color="row.obligatoria ? 'primary' : 'grey-6'">{{
            row.obligatoria ? 'Obligatoria' : 'Opcional'
          }}</q-badge></q-td
        >
      </template>
      <template #body-cell-activo="{ row }">
        <q-td
          ><q-badge :color="row.activo ? 'positive' : 'grey'">{{
            row.activo ? 'Activa' : 'Inactiva'
          }}</q-badge></q-td
        >
      </template>
      <template #body-cell-acciones="{ row }">
        <q-td class="text-right">
          <q-btn flat round icon="edit" aria-label="Editar" @click="abrir(row)" />
          <q-btn
            v-if="row.activo && !row.obligatoria"
            flat
            round
            color="negative"
            icon="block"
            aria-label="Desactivar"
            @click="desactivar(row)"
          />
        </q-td>
      </template>
    </q-table>

    <q-dialog v-model="dialogo" persistent>
      <q-card style="width: 520px; max-width: 90vw">
        <q-card-section class="text-h6">{{
          editando ? 'Editar área' : 'Nueva área'
        }}</q-card-section>
        <q-card-section class="column q-gutter-md">
          <q-input
            v-model="formulario.codigo"
            label="Código"
            hint="Corto y único, por ejemplo MOL"
            maxlength="10"
            outlined
          />
          <q-input v-model="formulario.nombre" label="Nombre" outlined />
          <q-input
            v-model="formulario.descripcion"
            label="Descripción / equipo (opcional)"
            type="textarea"
            autogrow
            outlined
          />
          <div class="row q-col-gutter-md">
            <div class="col-6">
              <q-input
                v-model="formulario.tarifa_mano_obra_hora"
                label="Mano de obra"
                type="number"
                min="0"
                step="0.01"
                prefix="$"
                suffix="/h"
                outlined
              />
            </div>
            <div class="col-6">
              <q-input
                v-model="formulario.costo_maquina_hora"
                label="Máquina"
                type="number"
                min="0"
                step="0.01"
                prefix="$"
                suffix="/h"
                outlined
              />
            </div>
          </div>
          <q-input
            v-model="formulario.orden_catalogo"
            label="Orden en el catálogo"
            hint="Define el orden relativo de las áreas obligatorias"
            type="number"
            min="1"
            outlined
          />
          <q-toggle v-model="formulario.obligatoria" label="Obligatoria (va en todo recorrido)" />
          <q-toggle
            v-if="editando"
            v-model="formulario.activo"
            label="Área activa"
            :disable="formulario.obligatoria"
          />
        </q-card-section>
        <q-card-actions align="right">
          <q-btn v-close-popup flat label="Cancelar" :disable="guardando" />
          <q-btn color="primary" unelevated label="Guardar" :loading="guardando" @click="guardar" />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>
