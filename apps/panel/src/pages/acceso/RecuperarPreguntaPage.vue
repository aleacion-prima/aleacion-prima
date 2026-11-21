<script setup lang="ts">
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useSesionStore } from '@/stores/sesion';
import { coincideCon, contrasenaSegura, correo, requerido } from '@/composables/reglas';

const router = useRouter();
const sesion = useSesionStore();

const email = ref('');
const pregunta = ref('');
const paso = ref<'correo' | 'respuesta'>('correo');
const verClave = ref(false);
const form = reactive({ respuesta: '', password: '', password_confirmation: '' });
const error = ref('');
const cargando = ref(false);

const buscarPregunta = async (): Promise<void> => {
  error.value = '';
  cargando.value = true;
  try {
    pregunta.value = await sesion.obtenerPregunta(email.value);
    paso.value = 'respuesta';
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : 'No se pudo obtener la pregunta';
  } finally {
    cargando.value = false;
  }
};

const restablecer = async (): Promise<void> => {
  error.value = '';
  cargando.value = true;
  try {
    await sesion.restablecerPorPregunta({
      email: email.value,
      respuesta: form.respuesta,
      password: form.password,
      password_confirmation: form.password_confirmation,
    });
    await router.push({ path: '/login', query: { restablecida: '1' } });
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : 'No se pudo restablecer la contraseña';
  } finally {
    cargando.value = false;
  }
};
</script>

<template>
  <q-page class="flex flex-center">
    <q-card flat bordered class="q-pa-xl" style="width: 400px">
      <div class="text-center q-mb-md">
        <div class="text-h5 text-weight-bold">ALEACIÓN PRIMA</div>
        <div class="text-secundario">Recuperar con pregunta de seguridad</div>
      </div>

      <q-form v-if="paso === 'correo'" class="column q-gutter-md" @submit="buscarPregunta">
        <q-input
          v-model="email"
          type="email"
          label="Correo electrónico"
          outlined
          autofocus
          autocomplete="username"
          lazy-rules
          :rules="[requerido('Escribe tu correo'), correo]"
        />
        <q-banner v-if="error" dense class="bg-red-1 text-negative">{{ error }}</q-banner>
        <q-btn type="submit" color="primary" label="Continuar" unelevated :loading="cargando" />
        <div class="text-center">
          <router-link to="/login" class="text-primary">Volver al inicio de sesión</router-link>
        </div>
      </q-form>

      <q-form v-else class="column q-gutter-md" @submit="restablecer">
        <div class="text-body2 text-weight-medium">{{ pregunta }}</div>
        <q-input
          v-model="form.respuesta"
          label="Tu respuesta"
          outlined
          autofocus
          :rules="[requerido('Escribe tu respuesta')]"
        />
        <q-input
          v-model="form.password"
          :type="verClave ? 'text' : 'password'"
          label="Nueva contraseña"
          hint="Mínimo 8 caracteres, con mayúsculas, minúsculas y números"
          outlined
          autocomplete="new-password"
          lazy-rules
          :rules="[requerido('Escribe tu nueva contraseña'), contrasenaSegura]"
        >
          <template #append>
            <q-icon
              :name="verClave ? 'visibility_off' : 'visibility'"
              class="cursor-pointer"
              @click="verClave = !verClave"
            />
          </template>
        </q-input>
        <q-input
          v-model="form.password_confirmation"
          :type="verClave ? 'text' : 'password'"
          label="Confirmar contraseña"
          outlined
          autocomplete="new-password"
          lazy-rules
          :rules="[requerido('Confirma tu contraseña'), coincideCon(() => form.password)]"
        />
        <q-banner v-if="error" dense class="bg-red-1 text-negative">{{ error }}</q-banner>
        <q-btn
          type="submit"
          color="primary"
          label="Guardar contraseña"
          unelevated
          :loading="cargando"
        />
      </q-form>
    </q-card>
  </q-page>
</template>
