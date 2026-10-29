<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue';
import { useQuasar } from 'quasar';
import type { AreaAsignada, Rol, Usuario } from '@aleacion-prima/types';
import { ROLES } from '@aleacion-prima/types';
import { API_URL, cabecerasApi } from '@/services/api';

interface FormularioUsuario {
  name: string;
  email: string;
  password: string;
  rol: Rol;
  area_ids: number[];
}

const $q = useQuasar();
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
  try {
    const [usuariosRes, areasRes] = await Promise.all([
      fetch(`${API_URL}/api/usuarios`, { credentials: 'include', headers: cabecerasApi() }),
      fetch(`${API_URL}/api/areas-disponibles`, {
        credentials: 'include',
        headers: cabecerasApi(),
      }),
    ]);
    if (!usuariosRes.ok || !areasRes.ok) throw new Error('No se pudieron cargar los usuarios');
    usuarios.value = (await usuariosRes.json()) as Usuario[];
    areas.value = (await areasRes.json()) as AreaAsignada[];
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: error instanceof Error ? error.message : 'Error de red',
    });
  } finally {
    cargando.value = false;
  }
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
  const url = esEdicion
    ? `${API_URL}/api/usuarios/${editando.value?.id ?? ''}`
    : `${API_URL}/api/usuarios`;
  const cuerpo = {
    name: formulario.name,
    email: formulario.email,
    ...(formulario.password ? { password: formulario.password } : {}),
    rol: formulario.rol,
    area_ids: formulario.rol === 'encargado' ? formulario.area_ids : [],
  };

  const res = await fetch(url, {
    method: esEdicion ? 'PUT' : 'POST',
    credentials: 'include',
    headers: cabecerasApi({ 'content-type': 'application/json' }),
    body: JSON.stringify(cuerpo),
  });
  if (!res.ok) {
    $q.notify({ type: 'negative', message: 'No se pudo guardar el usuario' });
    return;
  }
  dialogo.value = false;
  await cargar();
  $q.notify({ type: 'positive', message: esEdicion ? 'Usuario actualizado' : 'Usuario creado' });
};

const desactivarConfirmado = async (usuario: Usuario): Promise<void> => {
  const res = await fetch(`${API_URL}/api/usuarios/${usuario.id}`, {
    method: 'DELETE',
    credentials: 'include',
    headers: cabecerasApi(),
  });
  if (!res.ok) {
    $q.notify({ type: 'negative', message: 'No se pudo dar de baja al usuario' });
    return;
  }
  await cargar();
};

const desactivar = (usuario: Usuario): void => {
  $q.dialog({
    title: 'Dar de baja',
    message: `¿Desactivar a ${usuario.name}?`,
    cancel: true,
    persistent: true,
  }).onOk(() => {
    void desactivarConfirmado(usuario);
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
          <q-btn flat round icon="edit" aria-label="Editar" @click="abrir(row)" />
          <q-btn
            v-if="row.activo"
            flat
            round
            color="negative"
            icon="person_off"
            aria-label="Dar de baja"
            @click="desactivar(row)"
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
