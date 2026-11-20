<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue';
import { useQuasar } from 'quasar';
import { API_URL, cabecerasApi, useApi } from '@/services/api';

const $q = useQuasar();

const preguntas = ref<string[]>([]);
const preguntaActual = ref<string | null>(null);
const form = reactive({ pregunta: '', respuesta: '' });
const cargando = ref(true);
const guardando = ref(false);

const dosFactores = reactive({ activo: false, obligatorio: false });
const cargandoDF = ref(true);
const guardandoDF = ref(false);

const cargar = async (): Promise<void> => {
  cargando.value = true;
  try {
    const res = await fetch(`${API_URL}/api/perfil/pregunta-secreta`, {
      credentials: 'include',
      headers: cabecerasApi(),
    });
    if (!res.ok) throw new Error('No se pudo cargar tu pregunta de seguridad');
    const datos = (await res.json()) as { pregunta: string | null; preguntas: string[] };
    preguntas.value = datos.preguntas;
    preguntaActual.value = datos.pregunta;
    form.pregunta = datos.pregunta ?? '';
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: error instanceof Error ? error.message : 'Error de red',
    });
  } finally {
    cargando.value = false;
  }
};

const guardar = async (): Promise<void> => {
  guardando.value = true;
  try {
    const res = await fetch(`${API_URL}/api/perfil/pregunta-secreta`, {
      method: 'PUT',
      credentials: 'include',
      headers: cabecerasApi({ 'content-type': 'application/json' }),
      body: JSON.stringify({ pregunta: form.pregunta, respuesta: form.respuesta }),
    });
    if (!res.ok) {
      const cuerpo = (await res.json()) as { message?: string; errors?: Record<string, string[]> };
      const mensaje =
        Object.values(cuerpo.errors ?? {})[0]?.[0] ?? cuerpo.message ?? 'No se pudo guardar';
      $q.notify({ type: 'negative', message: mensaje });
      return;
    }
    form.respuesta = '';
    await cargar();
    $q.notify({ type: 'positive', message: 'Tu pregunta de seguridad quedó guardada.' });
  } finally {
    guardando.value = false;
  }
};

const cargarDosFactores = async (): Promise<void> => {
  cargandoDF.value = true;
  const { data, error } = await useApi('/perfil/segundo-factor').json<{
    activo: boolean;
    obligatorio: boolean;
  }>();
  if (error.value || !data.value) {
    $q.notify({ type: 'negative', message: 'No se pudo cargar la verificación en dos pasos' });
  } else {
    dosFactores.activo = data.value.activo;
    dosFactores.obligatorio = data.value.obligatorio;
  }
  cargandoDF.value = false;
};

const cambiarDosFactores = async (valor: boolean): Promise<void> => {
  guardandoDF.value = true;
  const { data, error } = await useApi('/perfil/segundo-factor')
    .put({ activo: valor })
    .json<{ activo?: boolean; message?: string; errors?: Record<string, string[]> }>();
  if (error.value) {
    const cuerpo = data.value ?? {};
    const mensaje =
      Object.values(cuerpo.errors ?? {})[0]?.[0] ?? cuerpo.message ?? 'No se pudo guardar';
    $q.notify({ type: 'negative', message: mensaje });
    dosFactores.activo = !valor;
  } else {
    dosFactores.activo = data.value?.activo ?? valor;
    $q.notify({ type: 'positive', message: data.value?.message ?? 'Guardado.' });
  }
  guardandoDF.value = false;
};

onMounted(cargar);
onMounted(cargarDosFactores);
</script>

<template>
  <q-page padding>
    <div class="text-h5 q-mb-md">Mi cuenta</div>
    <q-card flat bordered class="q-pa-lg" style="max-width: 560px">
      <div class="text-subtitle1 q-mb-sm">Pregunta de seguridad</div>
      <div class="text-body2 text-grey-7 q-mb-md">
        Sirve como segundo método para recuperar tu contraseña si no tienes acceso al correo.
        <span v-if="preguntaActual">Ya tienes una configurada; puedes cambiarla aquí.</span>
      </div>

      <q-form class="column q-gutter-md" @submit="guardar">
        <q-select
          v-model="form.pregunta"
          :options="preguntas"
          label="Pregunta"
          outlined
          :loading="cargando"
          :rules="[(v) => !!v || 'Elige una pregunta']"
        />
        <q-input
          v-model="form.respuesta"
          label="Respuesta"
          hint="No distingue mayúsculas ni espacios de más. Elige algo que recuerdes."
          outlined
          :rules="[(v) => !!v || 'Escribe tu respuesta']"
        />
        <div>
          <q-btn type="submit" color="primary" label="Guardar" unelevated :loading="guardando" />
        </div>
      </q-form>
    </q-card>

    <q-card flat bordered class="q-pa-lg q-mt-md" style="max-width: 560px">
      <div class="text-subtitle1 q-mb-sm">Verificación en dos pasos</div>
      <div class="text-body2 text-grey-7 q-mb-md">
        Al iniciar sesión te pediremos un código que enviamos a tu correo, además de la contraseña.
        <span v-if="dosFactores.obligatorio">
          Es obligatoria para tu rol de administrador y no se puede desactivar.
        </span>
      </div>

      <q-toggle
        :model-value="dosFactores.activo"
        :label="dosFactores.activo ? 'Activada' : 'Desactivada'"
        :disable="cargandoDF || guardandoDF || (dosFactores.obligatorio && dosFactores.activo)"
        @update:model-value="cambiarDosFactores"
      />
    </q-card>
  </q-page>
</template>
