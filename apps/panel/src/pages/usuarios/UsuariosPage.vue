<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue';
import { useQuasar } from 'quasar';
import type { AreaAsignada, Rol, Usuario } from '@aleacion-prima/types';
import { ROLES } from '@aleacion-prima/types';
import { useApi } from '@/services/api';
import { useSesionStore } from '@/stores/sesion';

interface FormularioUsuario {
  name: string;
  email: string;
  password: string;
  rol: Rol;
  area_ids: number[];
}

const $q = useQuasar();
const sesion = useSesionStore();
const usuarios = ref<Usuario[]>([]);
const areas = ref<AreaAsignada[]>([]);
const dialogo = ref(false);
const editando = ref<Usuario | null>(null);
const cargando = ref(true);
const formulario = reactive<FormularioUsuario>({
  name: '',
  email: '',
  password: '',
  rol: 'encargado',
  area_ids: [],
});

const cargar = async (): Promise<void> => {
  cargando.value = true;
  const [usuariosRes, areasRes] = await Promise.all([
    useApi<Usuario[]>('/usuarios').json<Usuario[]>(),
    useApi<AreaAsignada[]>('/areas-disponibles').json<AreaAsignada[]>(),
  ]);
  cargando.value = false;

  if (usuariosRes.error.value || areasRes.error.value) {
    $q.notify({ type: 'negative', message: 'No se pudieron cargar los usuarios' });
    return;
  }

  usuarios.value = usuariosRes.data.value ?? [];
  areas.value = areasRes.data.value ?? [];
};

const abrir = (usuario?: Usuario): void => {
  editando.value = usuario ?? null;
  formulario.name = usuario?.name ?? '';
  formulario.email = usuario?.email ?? '';
  formulario.password = '';
  formulario.rol = usuario?.rol ?? 'encargado';
  formulario.area_ids = usuario?.areas?.map((area) => area.id) ?? [];
  dialogo.value = true;
};

const guardar = async (): Promise<void> => {
  const esEdicion = editando.value !== null;
  const cuerpo = {
    name: formulario.name,
    email: formulario.email,
    ...(formulario.password ? { password: formulario.password } : {}),
    rol: formulario.rol,
    area_ids: formulario.rol === 'encargado' ? formulario.area_ids : [],
  };

  const { error } = esEdicion
    ? await useApi(`/usuarios/${editando.value?.id ?? ''}`)
        .put(cuerpo)
        .json<Usuario>()
    : await useApi('/usuarios').post(cuerpo).json<Usuario>();

  if (error.value) {
    $q.notify({ type: 'negative', message: 'No se pudo guardar el usuario' });
    return;
  }
  dialogo.value = false;
  await cargar();
  $q.notify({ type: 'positive', message: esEdicion ? 'Usuario actualizado' : 'Usuario creado' });
};

const esUnoMismo = (usuario: Usuario): boolean => usuario.id === sesion.usuario?.id;

const alternarActivo = async (usuario: Usuario): Promise<void> => {
  const { error } = await useApi(`/usuarios/${usuario.id}`)
    .put({
      name: usuario.name,
      email: usuario.email,
      rol: usuario.rol,
      area_ids: usuario.areas?.map((area) => area.id) ?? [],
      activo: !usuario.activo,
    })
    .json<Usuario>();

  if (error.value) {
    $q.notify({ type: 'negative', message: 'No se pudo cambiar el estado del usuario' });
    return;
  }
  await cargar();
  $q.notify({
    type: 'positive',
    message: usuario.activo ? 'Usuario desactivado' : 'Usuario activado',
  });
};

const alternarEstado = (usuario: Usuario): void => {
  if (esUnoMismo(usuario)) {
    $q.notify({ type: 'warning', message: 'No puedes desactivarte a ti mismo' });
    return;
  }

  const activar = !usuario.activo;
  $q.dialog({
    title: activar ? 'Activar usuario' : 'Desactivar usuario',
    message: activar ? `¿Activar a ${usuario.name}?` : `¿Desactivar a ${usuario.name}?`,
    cancel: true,
    persistent: true,
  }).onOk(() => {
    void alternarActivo(usuario);
  });
};

const eliminarConfirmado = async (usuario: Usuario): Promise<void> => {
  const { error } = await useApi(`/usuarios/${usuario.id}`).delete().text();

  if (error.value) {
    $q.notify({ type: 'negative', message: 'No se pudo eliminar al usuario' });
    return;
  }
  await cargar();
  $q.notify({ type: 'positive', message: 'Usuario eliminado' });
};

const eliminar = (usuario: Usuario): void => {
  $q.dialog({
    title: 'Eliminar usuario',
    message: `¿Eliminar a ${usuario.name}? Su cuenta quedará inactiva y se cerrarán sus sesiones.`,
    cancel: true,
    persistent: true,
  }).onOk(() => {
    void eliminarConfirmado(usuario);
  });
};

onMounted(cargar);
</script>

<template>
  <q-page padding>
    <div class="row items-center justify-between q-mb-lg">
      <div class="text-h4">Usuarios y roles</div>
      <q-btn color="primary" unelevated icon="add" label="Nuevo usuario" @click="abrir()" />
    </div>

    <q-table
      flat
      bordered
      :rows="usuarios"
      :columns="[
        { name: 'name', label: 'Nombre', field: 'name', align: 'left' },
        { name: 'email', label: 'Correo', field: 'email', align: 'left' },
        { name: 'rol', label: 'Rol', field: 'rol', align: 'left' },
        { name: 'activo', label: 'Estado', field: 'activo', align: 'left' },
        { name: 'acciones', label: '', field: 'id', align: 'right' },
      ]"
      row-key="id"
      :loading="cargando"
    >
      <template #body-cell-rol="{ row }">
        <q-td
          ><q-badge color="secondary">{{ row.rol }}</q-badge></q-td
        >
      </template>
      <template #body-cell-activo="{ row }">
        <q-td
          ><q-badge :color="row.activo ? 'positive' : 'grey'">{{
            row.activo ? 'Activo' : 'Inactivo'
          }}</q-badge></q-td
        >
      </template>
      <template #body-cell-acciones="{ row }">
        <q-td class="text-right">
          <q-btn
            flat
            round
            :color="row.activo ? 'positive' : 'grey'"
            :icon="row.activo ? 'toggle_on' : 'toggle_off'"
            :aria-label="row.activo ? 'Desactivar' : 'Activar'"
            :disable="esUnoMismo(row)"
            @click="alternarEstado(row)"
          >
            <q-tooltip v-if="esUnoMismo(row)">No puedes desactivarte a ti mismo</q-tooltip>
          </q-btn>
          <q-btn flat round icon="edit" aria-label="Editar" @click="abrir(row)" />
          <q-btn
            v-if="!esUnoMismo(row)"
            flat
            round
            color="negative"
            icon="delete"
            aria-label="Eliminar"
            @click="eliminar(row)"
          />
        </q-td>
      </template>
    </q-table>

    <q-dialog v-model="dialogo">
      <q-card style="width: 520px; max-width: 90vw">
        <q-card-section class="text-h6">{{
          editando ? 'Editar usuario' : 'Nuevo usuario'
        }}</q-card-section>
        <q-card-section class="column q-gutter-md">
          <q-input v-model="formulario.name" label="Nombre" outlined />
          <q-input v-model="formulario.email" label="Correo" type="email" outlined />
          <q-input
            v-model="formulario.password"
            :label="editando ? 'Nueva contraseña (opcional)' : 'Contraseña'"
            type="password"
            outlined
          />
          <q-select v-model="formulario.rol" :options="ROLES" label="Rol" outlined emit-value />
          <q-select
            v-if="formulario.rol === 'encargado'"
            v-model="formulario.area_ids"
            :options="areas"
            option-label="nombre"
            option-value="id"
            label="Áreas que cubre"
            multiple
            emit-value
            map-options
            outlined
          />
        </q-card-section>
        <q-card-actions align="right">
          <q-btn v-close-popup flat label="Cancelar" />
          <q-btn color="primary" unelevated label="Guardar" @click="guardar" />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>
