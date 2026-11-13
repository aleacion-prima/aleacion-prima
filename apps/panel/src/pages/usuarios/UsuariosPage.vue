<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue';
import { useQuasar } from 'quasar';
import type { AreaAsignada, Rol, Usuario } from '@aleacion-prima/types';
import { ROLES } from '@aleacion-prima/types';
import { etiquetaRol } from '@/catalogos/roles';
import { useApi } from '@/services/api';
import { useSesionStore } from '@/stores/sesion';

interface FormularioUsuario {
  name: string;
  email: string;
  password: string;
  rol: Rol;
  area_ids: number[];
  activo: boolean;
}

type VistaUsuarios = 'en_uso' | 'bajas';

const $q = useQuasar();
const sesion = useSesionStore();
const usuarios = ref<Usuario[]>([]);
const areas = ref<AreaAsignada[]>([]);
const dialogo = ref(false);
const editando = ref<Usuario | null>(null);
const cargando = ref(true);
const vista = ref<VistaUsuarios>('en_uso');
const fechaBaja = new Intl.DateTimeFormat('es-MX', { dateStyle: 'medium', timeStyle: 'short' });
const formulario = reactive<FormularioUsuario>({
  name: '',
  email: '',
  password: '',
  rol: 'encargado',
  area_ids: [],
  activo: true,
});

const esUnoMismo = (usuario: Usuario | null): boolean =>
  usuario !== null && usuario.id === sesion.usuario?.id;

const enUso = computed(() => usuarios.value.filter((usuario) => usuario.dado_de_baja_en === null));
const bajas = computed(() => usuarios.value.filter((usuario) => usuario.dado_de_baja_en !== null));
const visibles = computed(() => (vista.value === 'en_uso' ? enUso.value : bajas.value));

const fechaDeBaja = (usuario: Usuario): string =>
  usuario.dado_de_baja_en === null ? '—' : fechaBaja.format(new Date(usuario.dado_de_baja_en));

const areasAsignadas = (usuario: Usuario): string => {
  if (!usuario.areas?.length) return '—';

  return usuario.areas.map((area) => area.nombre).join(', ');
};

const cargar = async (): Promise<void> => {
  cargando.value = true;
  const [usuariosRes, areasRes] = await Promise.all([
    useApi<Usuario[]>('/usuarios?incluir_bajas=1').json<Usuario[]>(),
    useApi<AreaAsignada[]>('/areas?activo=1').json<AreaAsignada[]>(),
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
  formulario.activo = usuario?.activo ?? true;
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
    ...(esEdicion && !esUnoMismo(editando.value) ? { activo: formulario.activo } : {}),
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

const darDeBajaConfirmado = async (usuario: Usuario): Promise<void> => {
  const { error } = await useApi(`/usuarios/${usuario.id}`).delete().text();

  if (error.value) {
    $q.notify({ type: 'negative', message: 'No se pudo dar de baja al usuario' });
    return;
  }
  dialogo.value = false;
  await cargar();
  $q.notify({ type: 'positive', message: 'Usuario dado de baja' });
};

const darDeBaja = (usuario: Usuario): void => {
  $q.dialog({
    title: 'Dar de baja',
    message: `¿Dar de baja a ${usuario.name}? Dejará de aparecer en la lista y se cerrarán sus sesiones. Su cuenta y su historial se conservan, y su correo quedará libre para otra cuenta.`,
    cancel: true,
    persistent: true,
  }).onOk(() => {
    void darDeBajaConfirmado(usuario);
  });
};

onMounted(cargar);
</script>

<template>
  <q-page padding>
    <div class="row items-start justify-between q-mb-lg">
      <div>
        <div class="text-h4">Usuarios y roles</div>
        <div class="text-secundario q-mt-xs">
          Administra las cuentas de acceso, los roles y las áreas asignadas.
        </div>
      </div>
      <q-btn unelevated no-caps color="primary" icon="add" label="Nuevo usuario" @click="abrir()" />
    </div>

    <div v-if="cargando" class="row justify-center q-pa-xl">
      <q-spinner color="primary" size="40px" />
    </div>

    <q-card v-else flat bordered>
      <q-tabs
        v-model="vista"
        dense
        no-caps
        align="left"
        active-color="primary"
        indicator-color="primary"
        class="text-secundario"
      >
        <q-tab name="en_uso" :label="`En uso (${enUso.length})`" />
        <q-tab name="bajas" :label="`Dados de baja (${bajas.length})`" />
      </q-tabs>

      <q-separator />

      <div v-if="!visibles.length" class="q-pa-xl text-center text-secundario">
        {{
          vista === 'en_uso'
            ? 'No hay usuarios en uso. Crea uno con «Nuevo usuario».'
            : 'No hay usuarios dados de baja.'
        }}
      </div>

      <div v-else class="row q-col-gutter-md q-pa-md">
        <div v-for="usuario in visibles" :key="usuario.id" class="col-12 col-sm-6 col-lg-4">
          <q-card flat bordered class="tarjeta-usuario column no-wrap">
            <q-card-section class="q-pb-sm">
              <div class="row items-center justify-between">
                <div class="text-h6">{{ usuario.name }}</div>
                <q-chip
                  dense
                  :color="
                    usuario.dado_de_baja_en ? 'grey-8' : usuario.activo ? 'positive' : 'grey-6'
                  "
                  text-color="white"
                  :label="
                    usuario.dado_de_baja_en
                      ? 'Dado de baja'
                      : usuario.activo
                        ? 'Activo'
                        : 'Inactivo'
                  "
                  class="text-weight-medium"
                />
              </div>
            </q-card-section>

            <q-card-section class="q-pt-none column q-gutter-y-sm">
              <div class="campo-usuario">
                <div class="campo-etiqueta">Rol del sistema</div>
                <div class="text-info">{{ etiquetaRol(usuario.rol) }}</div>
              </div>
              <div class="campo-usuario">
                <div class="campo-etiqueta">
                  {{ usuario.dado_de_baja_en ? 'Correo antes de la baja' : 'Correo electrónico' }}
                </div>
                <div class="campo-valor">{{ usuario.email }}</div>
              </div>
              <div class="campo-usuario">
                <div class="campo-etiqueta">Áreas asignadas</div>
                <div class="campo-valor">{{ areasAsignadas(usuario) }}</div>
              </div>
              <div v-if="usuario.dado_de_baja_en" class="campo-usuario">
                <div class="campo-etiqueta">Dado de baja el</div>
                <div class="campo-valor">{{ fechaDeBaja(usuario) }}</div>
              </div>
            </q-card-section>

            <q-card-actions
              v-if="usuario.dado_de_baja_en === null"
              align="right"
              class="q-pa-md q-pt-none q-mt-auto"
            >
              <q-btn
                outline
                no-caps
                color="secondary"
                label="Editar usuario"
                icon-right="arrow_forward"
                @click="abrir(usuario)"
              />
            </q-card-actions>
          </q-card>
        </div>
      </div>
    </q-card>

    <q-dialog v-model="dialogo">
      <q-card style="width: 520px; max-width: 90vw">
        <q-card-section class="text-h6">{{
          editando ? 'Editar usuario' : 'Nuevo usuario'
        }}</q-card-section>
        <q-card-section class="column q-gutter-md">
          <q-input v-model="formulario.name" label="Nombre" outlined autofocus />
          <q-input v-model="formulario.email" label="Correo" type="email" outlined />
          <q-input
            v-model="formulario.password"
            :label="editando ? 'Nueva contraseña (opcional)' : 'Contraseña'"
            type="password"
            outlined
          />
          <q-select
            v-model="formulario.rol"
            :options="ROLES"
            label="Rol"
            outlined
            emit-value
            :option-label="etiquetaRol"
            :disable="esUnoMismo(editando)"
          />
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
          <q-toggle
            v-if="editando"
            v-model="formulario.activo"
            label="Cuenta activa"
            :disable="esUnoMismo(editando)"
          />
          <div v-if="editando && esUnoMismo(editando)" class="text-secundario text-caption">
            No puedes desactivar tu cuenta ni quitarte el rol de administrador.
          </div>
        </q-card-section>
        <q-card-actions>
          <q-btn
            v-if="editando && !esUnoMismo(editando)"
            flat
            no-caps
            color="negative"
            icon="person_off"
            label="Dar de baja"
            @click="darDeBaja(editando)"
          />
          <q-space />
          <q-btn v-close-popup flat label="Cancelar" />
          <q-btn color="primary" unelevated label="Guardar" @click="guardar" />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<style scoped lang="scss">
.tarjeta-usuario {
  border-radius: 8px;
}

.campo-usuario {
  padding: 8px 12px;
  border: 1px solid $acero-claro;
  border-radius: 6px;
  background: $blanco-humo;
}

.campo-etiqueta {
  color: $grafito-medio;
  font-size: 12px;
  line-height: 16px;
}

.campo-valor {
  color: $dark;
  overflow-wrap: anywhere;
}
</style>
