<script setup lang="ts">
import type { Imprevisto } from '@aleacion-prima/types';
import { ETIQUETAS_TIPO_IMPREVISTO } from '@/catalogos/imprevistos';

defineProps<{ imprevistos: Imprevisto[] }>();

const fecha = new Intl.DateTimeFormat('es-MX', {
  day: 'numeric',
  month: 'short',
  hour: '2-digit',
  minute: '2-digit',
  hourCycle: 'h23',
});
</script>

<template>
  <q-list bordered separator class="imprevistos-movil">
    <q-item v-for="imprevisto in imprevistos" :key="imprevisto.id">
      <q-item-section avatar>
        <q-icon
          :name="imprevisto.resuelto ? 'check_circle' : 'warning'"
          :color="imprevisto.resuelto ? 'positive' : 'warning'"
        />
      </q-item-section>
      <q-item-section>
        <q-item-label>
          {{ ETIQUETAS_TIPO_IMPREVISTO[imprevisto.tipo] }}
          <span v-if="imprevisto.area" class="codigo text-secundario">
            · {{ imprevisto.area.codigo }}</span
          >
        </q-item-label>
        <q-item-label caption>{{ imprevisto.descripcion }}</q-item-label>
      </q-item-section>
      <q-item-section side top>
        <q-item-label caption>{{ fecha.format(new Date(imprevisto.ocurrido_en)) }}</q-item-label>
        <q-chip
          dense
          text-color="white"
          :color="imprevisto.resuelto ? 'positive' : 'warning'"
          :label="imprevisto.resuelto ? 'Resuelto' : 'Abierto'"
          class="q-ma-none q-mt-xs"
        />
      </q-item-section>
    </q-item>
  </q-list>
</template>

<style scoped lang="scss">
.imprevistos-movil {
  border-radius: 12px;
  background: white;
}
</style>
