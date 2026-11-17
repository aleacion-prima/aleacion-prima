<script setup lang="ts">
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useSesionStore } from '@/stores/sesion';
import { correo, requerido } from '@/composables/reglas';

const router = useRouter();
const sesion = useSesionStore();

const credenciales = reactive({ email: '', password: '' });
const error = ref('');
const cargando = ref(false);

const entrar = async (): Promise<void> => {
  error.value = '';
  cargando.value = true;
  try {
    await sesion.iniciar(credenciales.email, credenciales.password);
    await router.replace('/');
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : 'No se pudo iniciar sesión';
  } finally {
    cargando.value = false;
  }
};
</script>

<template>
  <q-page class="acceso-movil">
    <q-form class="acceso-movil__formulario" @submit="entrar">
      <h1 class="acceso-movil__marca">ALEACIÓN PRIMA</h1>

      <q-input
        v-model="credenciales.email"
        type="email"
        label="Correo electrónico"
        stack-label
        outlined
        dense
        bg-color="white"
        autocomplete="username"
        inputmode="email"
        lazy-rules
        :rules="[requerido('Escribe tu correo'), correo]"
        hide-bottom-space
      />

      <q-input
        v-model="credenciales.password"
        type="password"
        label="Contraseña"
        stack-label
        outlined
        dense
        bg-color="white"
        autocomplete="current-password"
        :rules="[requerido('Escribe tu contraseña')]"
        hide-bottom-space
      />

      <q-banner v-if="error" dense rounded class="bg-red-1 text-negative">{{ error }}</q-banner>

      <q-btn
        type="submit"
        color="primary"
        label="Acceder"
        unelevated
        no-caps
        class="full-width"
        :loading="cargando"
      />
    </q-form>
  </q-page>
</template>

<style scoped lang="scss">
.acceso-movil {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px;
  background: $blanco-humo;
}

.acceso-movil__formulario {
  display: flex;
  flex-direction: column;
  gap: 16px;
  width: 100%;
  max-width: 400px;
}

.acceso-movil__marca {
  margin: 0 0 8px;
  font-family: $fuente-titulos;
  font-size: 28px;
  font-weight: 600;
  line-height: 32px;
  text-align: center;
  color: $dark;
}
</style>
