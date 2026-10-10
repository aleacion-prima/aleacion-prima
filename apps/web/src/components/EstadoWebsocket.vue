<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue';
import { desconectarEcho, obtenerEcho } from '../services/echo';

// Componente de prueba: solo verifica que el cliente logre conectarse
// al servidor de Reverb. No se suscribe a ningun canal todavia.
const estado = ref<'connecting' | 'connected' | 'reconnecting' | 'disconnected' | 'failed'>(
  'connecting',
);

let cancelarSuscripcion: (() => void) | undefined;

onMounted(() => {
  const echo = obtenerEcho();
  estado.value = echo.connectionStatus();
  cancelarSuscripcion = echo.connector.onConnectionChange((status) => {
    estado.value = status;
  });
});

onUnmounted(() => {
  cancelarSuscripcion?.();
  desconectarEcho();
});
</script>

<template>
  <p id="estado-websocket">Websocket (Reverb): {{ estado }}</p>
</template>

<style scoped>
#estado-websocket {
  font-family: monospace;
  padding: 8px 12px;
  border-radius: 8px;
  background: #111827;
  color: #fff;
  display: inline-block;
}
</style>
