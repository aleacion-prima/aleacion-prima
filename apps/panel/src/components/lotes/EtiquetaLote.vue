<script setup lang="ts">
import { computed } from 'vue';
import { QrcodeSvg } from 'qrcode.vue';
import type { Lote } from '@aleacion-prima/types';
import { piezas } from '@/composables/formato';
import LogoMarca from '@/components/navegacion/LogoMarca.vue';

const props = defineProps<{
  lote: Pick<Lote, 'codigo' | 'producto' | 'cantidad_objetivo' | 'created_at'>;
}>();

const fecha = new Intl.DateTimeFormat('es-MX', { day: 'numeric', month: 'short', year: 'numeric' });

const creado = computed(() => fecha.format(new Date(props.lote.created_at)));
</script>

<template>
  <div class="etiqueta">
    <QrcodeSvg :value="lote.codigo" :size="175" level="Q" :margin="4" class="etiqueta__qr" />
    <div class="etiqueta__datos">
      <div class="etiqueta__marca">
        <LogoMarca class="etiqueta__logo" />
        ALEACIÓN PRIMA
      </div>
      <div class="etiqueta__codigo codigo">{{ lote.codigo }}</div>
      <div class="etiqueta__producto">{{ lote.producto.nombre }}</div>
      <div class="etiqueta__detalle">
        {{ lote.producto.codigo }} · {{ piezas(lote.cantidad_objetivo) }}
      </div>
      <div class="etiqueta__detalle">Creado {{ creado }}</div>
    </div>
  </div>
</template>

<style scoped lang="scss">
.etiqueta {
  display: flex;
  align-items: center;
  gap: 4mm;
  width: 100mm;
  height: 60mm;
  padding: 4mm;
  overflow: hidden;
  background: white;
  color: $dark;
  border: 1px dashed $dark;
  break-inside: avoid;
}

.etiqueta__qr {
  flex-shrink: 0;
  width: 46mm;
  height: 46mm;
}

.etiqueta__datos {
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-width: 0;
}

.etiqueta__marca {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  font-weight: 500;
  line-height: 16px;
  letter-spacing: 0.5px;
}

.etiqueta__logo {
  width: 20px;
  height: 20px;
}

.etiqueta__codigo {
  font-size: 17px;
  font-weight: 500;
  line-height: 24px;
  white-space: nowrap;
}

.etiqueta__producto {
  font-size: 14px;
  line-height: 20px;
}

.etiqueta__detalle {
  font-size: 12px;
  line-height: 16px;
  color: $grafito-medio;
}

@media print {
  .etiqueta {
    border: none;
  }
}
</style>
