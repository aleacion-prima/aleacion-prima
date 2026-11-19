<script setup lang="ts">
import { onUnmounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useSesionStore } from '@/stores/sesion';

const router = useRouter();
const route = useRoute();
const sesion = useSesionStore();

const codigo = ref('');
const error = ref('');
const mensaje = ref('');
const cargando = ref(false);
const reenviando = ref(false);
const segundos = ref(0);

let temporizador: ReturnType<typeof setInterval> | undefined;

const iniciarEspera = (): void => {
  segundos.value = 60;
  temporizador = setInterval(() => {
    segundos.value -= 1;
    if (segundos.value <= 0 && temporizador) clearInterval(temporizador);
  }, 1000);
};

const verificar = async (): Promise<void> => {
  error.value = '';
  cargando.value = true;
  try {
    await sesion.verificarCodigo(codigo.value);
    await router.push(typeof route.query.volver === 'string' ? route.query.volver : '/');
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : 'No se pudo verificar el código';
  } finally {
    cargando.value = false;
  }
};

const reenviar = async (): Promise<void> => {
  error.value = '';
  mensaje.value = '';
  reenviando.value = true;
  try {
    mensaje.value = await sesion.reenviarCodigo();
    iniciarEspera();
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : 'No se pudo reenviar el código';
  } finally {
    reenviando.value = false;
  }
};

onUnmounted(() => {
  if (temporizador) clearInterval(temporizador);
});
</script>

<template>
  <q-page class="flex flex-center">
    <q-card flat bordered class="q-pa-xl" style="width: 400px">
      <q-form class="column q-gutter-md" @submit="verificar">
        <div class="text-center">
          <div class="text-h5 text-weight-bold">ALEACIÓN PRIMA</div>
          <div class="text-secundario">Verificación en dos pasos</div>
        </div>

        <div class="text-body2 text-grey-7">
          Te enviamos un código de 6 dígitos a tu correo. Escríbelo para terminar de iniciar sesión.
        </div>

        <q-input
          v-model="codigo"
          label="Código"
          outlined
          autofocus
          inputmode="numeric"
          mask="######"
          :rules="[(v) => /^\d{6}$/.test(v) || 'Escribe los 6 dígitos']"
        />

        <q-banner v-if="mensaje" dense class="bg-green-1 text-positive">{{ mensaje }}</q-banner>
        <q-banner v-if="error" dense class="bg-red-1 text-negative">{{ error }}</q-banner>

        <q-btn type="submit" color="primary" label="Verificar" unelevated :loading="cargando" />

        <div class="text-center">
          <q-btn
            type="button"
            flat
            no-caps
            color="primary"
            :label="segundos > 0 ? `Reenviar en ${segundos}s` : 'Reenviar código'"
            :loading="reenviando"
            :disable="segundos > 0"
            @click="reenviar"
          />
        </div>

        <div class="text-center">
          <router-link to="/login" class="text-primary">Volver al inicio de sesión</router-link>
        </div>
      </q-form>
    </q-card>
  </q-page>
</template>
