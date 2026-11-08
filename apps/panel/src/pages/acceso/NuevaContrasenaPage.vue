<script setup lang="ts">
import { reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useSesionStore } from '@/stores/sesion';

const route = useRoute();
const router = useRouter();
const sesion = useSesionStore();

const token = typeof route.query.token === 'string' ? route.query.token : '';
const email = typeof route.query.email === 'string' ? route.query.email : '';
const enlaceValido = Boolean(token && email);

const form = reactive({ password: '', password_confirmation: '' });
const verClave = ref(false);
const error = ref('');
const cargando = ref(false);

const guardar = async (): Promise<void> => {
  error.value = '';
  cargando.value = true;
  try {
    await sesion.restablecer({
      token,
      email,
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
      <q-form v-if="enlaceValido" class="column q-gutter-md" @submit="guardar">
        <div class="text-center">
          <div class="text-h5 text-weight-bold">ALEACIÓN PRIMA</div>
          <div class="text-secundario">Nueva contraseña</div>
        </div>

        <q-input :model-value="email" label="Correo electrónico" outlined readonly />

        <q-input
          v-model="form.password"
          :type="verClave ? 'text' : 'password'"
          label="Nueva contraseña"
          hint="Mínimo 8 caracteres, con mayúsculas, minúsculas y números"
          outlined
          autofocus
          autocomplete="new-password"
          :rules="[(v) => !!v || 'Escribe tu nueva contraseña']"
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
          :rules="[(v) => !!v || 'Confirma tu contraseña']"
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

      <div v-else class="column q-gutter-md text-center">
        <div class="text-h6">Enlace no válido</div>
        <div class="text-body2 text-grey-7">
          El enlace para restablecer la contraseña está incompleto o es incorrecto. Solicita uno
          nuevo.
        </div>
        <router-link to="/olvide-contrasena" class="text-primary">
          Solicitar un enlace nuevo
        </router-link>
      </div>
    </q-card>
  </q-page>
</template>
