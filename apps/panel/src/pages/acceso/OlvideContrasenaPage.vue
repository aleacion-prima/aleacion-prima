<script setup lang="ts">
import { onUnmounted, ref } from 'vue';
import { useSesionStore } from '@/stores/sesion';
import { correo, requerido } from '@/composables/reglas';

const sesion = useSesionStore();

const email = ref('');
const mensaje = ref('');
const error = ref('');
const cargando = ref(false);
const segundos = ref(0);

let temporizador: ReturnType<typeof setInterval> | undefined;

const iniciarEspera = (): void => {
  segundos.value = 60;
  temporizador = setInterval(() => {
    segundos.value -= 1;
    if (segundos.value <= 0 && temporizador) clearInterval(temporizador);
  }, 1000);
};

const enviar = async (): Promise<void> => {
  error.value = '';
  mensaje.value = '';
  cargando.value = true;
  try {
    mensaje.value = await sesion.recuperar(email.value);
    iniciarEspera();
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : 'No se pudo enviar el enlace';
  } finally {
    cargando.value = false;
  }
};

onUnmounted(() => {
  if (temporizador) clearInterval(temporizador);
});
</script>

<template>
  <q-page class="flex flex-center">
    <q-card flat bordered class="q-pa-xl" style="width: 400px">
      <q-form class="column q-gutter-md" @submit="enviar">
        <div class="text-center">
          <div class="text-h5 text-weight-bold">ALEACIÓN PRIMA</div>
          <div class="text-secundario">Recuperar contraseña</div>
        </div>

        <div class="text-body2 text-grey-7">
          Escribe tu correo y te enviaremos un enlace para crear una contraseña nueva.
        </div>

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

        <q-banner v-if="mensaje" dense class="bg-green-1 text-positive">{{ mensaje }}</q-banner>
        <q-banner v-if="error" dense class="bg-red-1 text-negative">{{ error }}</q-banner>

        <q-btn
          type="submit"
          color="primary"
          :label="segundos > 0 ? `Reintentar en ${segundos}s` : 'Enviar enlace'"
          unelevated
          :loading="cargando"
          :disable="segundos > 0"
        />

        <div class="text-center">
          <router-link to="/recuperar-pregunta" class="text-primary"
            >¿No tienes acceso al correo? Usa tu pregunta de seguridad</router-link
          >
        </div>

        <div class="text-center">
          <router-link to="/login" class="text-primary">Volver al inicio de sesión</router-link>
        </div>
      </q-form>
    </q-card>
  </q-page>
</template>
