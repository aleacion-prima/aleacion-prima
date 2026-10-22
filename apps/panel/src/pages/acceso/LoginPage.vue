<script setup lang="ts">
import { reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useSesionStore } from '@/stores/sesion';

const router = useRouter();
const route = useRoute();
const sesion = useSesionStore();

const credenciales = reactive({ email: '', password: '' });
const verClave = ref(false);
const error = ref('');
const cargando = ref(false);

const entrar = async (): Promise<void> => {
  error.value = '';
  cargando.value = true;
  try {
    await sesion.iniciar(credenciales.email, credenciales.password);
    await router.push(typeof route.query.volver === 'string' ? route.query.volver : '/');
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : 'No se pudo iniciar sesión';
  } finally {
    cargando.value = false;
  }
};
</script>

<template>
  <q-page class="flex flex-center">
    <q-card flat bordered class="q-pa-xl" style="width: 400px">
      <q-form class="column q-gutter-md" @submit="entrar">
        <div class="text-center">
          <div class="text-h5 text-weight-bold">ALEACIÓN PRIMA</div>
          <div class="text-secundario">Acceso al sistema</div>
        </div>

        <q-input
          v-model="credenciales.email"
          type="email"
          label="Correo electrónico"
          outlined
          autofocus
          autocomplete="username"
          :rules="[(v) => !!v || 'Escribe tu correo']"
        />

        <q-input
          v-model="credenciales.password"
          :type="verClave ? 'text' : 'password'"
          label="Contraseña"
          outlined
          autocomplete="current-password"
          :rules="[(v) => !!v || 'Escribe tu contraseña']"
        >
          <template #append>
            <q-icon
              :name="verClave ? 'visibility_off' : 'visibility'"
              class="cursor-pointer"
              @click="verClave = !verClave"
            />
          </template>
        </q-input>

        <q-banner v-if="error" dense class="bg-red-1 text-negative">{{ error }}</q-banner>

        <q-btn type="submit" color="primary" label="Ingresar" unelevated :loading="cargando" />
      </q-form>
    </q-card>
  </q-page>
</template>
