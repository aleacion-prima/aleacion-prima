<template>
  <div class="recorrido" :class="{ 'recorrido--vertical': vertical }">
    <template v-for="(parada, i) in paradas" :key="`${parada.codigo}-${i}`">
      <div v-if="i > 0" class="recorrido__enlace" :class="`recorrido__enlace--${enlace(i)}`" />
      <ParadaRecorrido
        :orden="i + 1"
        :codigo="parada.codigo"
        :nombre="parada.nombre"
        :estado="parada.estado"
        :tiempo="parada.tiempo ?? ''"
        :piezas="parada.piezas ?? ''"
        :aviso="parada.aviso ?? ''"
        :obligatoria="parada.obligatoria ?? false"
      />
    </template>
  </div>
</template>

<script setup lang="ts">
import type { ParadaLote } from '@/catalogos/areas';
import ParadaRecorrido from './ParadaRecorrido.vue';

const props = withDefaults(defineProps<{ paradas: ParadaLote[]; vertical?: boolean }>(), {
  vertical: false,
});

const enlace = (i: number): 'terminado' | 'en_curso' | 'pendiente' => {
  const anterior = props.paradas[i - 1]?.estado;
  const actual = props.paradas[i]?.estado;
  if (anterior === 'terminada' && actual === 'terminada') return 'terminado';
  if (anterior === 'terminada' && actual === 'en_curso') return 'en_curso';
  return 'pendiente';
};
</script>

<style scoped lang="scss">
.recorrido {
  display: flex;
  align-items: center;

  &__enlace {
    flex: 1 1 24px;
    min-width: 16px;
    height: 0;
    border-top: 3px dotted $acero-claro;

    &--terminado {
      border-top: 3px solid $positive;
    }

    &--en_curso {
      border-top: 3px dashed $accent;
    }
  }

  &--vertical {
    flex-direction: column;
    align-items: stretch;

    .parada {
      width: auto;
    }

    .recorrido__enlace {
      flex: 0 0 24px;
      width: 0;
      height: 24px;
      margin-left: 24px;
      border-top: 0;
      border-left: 3px dotted $acero-claro;

      &--terminado {
        border-left: 3px solid $positive;
      }

      &--en_curso {
        border-left: 3px dashed $accent;
      }
    }
  }
}
</style>
