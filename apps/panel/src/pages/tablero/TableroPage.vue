<script setup lang="ts">
import EstadoVacio from '@/components/comunes/EstadoVacio.vue';
</script>

<template>
  <q-page padding>
    <div class="text-h4">Estado general de la planta</div>
    <EstadoVacio
      titulo="Sin lotes en proceso"
      descripcion="Aquí se verá el recorrido de cada lote en vivo"
      icono="timeline"
    />
  </q-page>
</template>
