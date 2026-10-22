<script setup lang="ts">
import { useQuasar } from 'quasar';
import type { ParadaLote } from '@/catalogos/areas';
import ParadaRecorrido from './ParadaRecorrido.vue';

const $q = useQuasar();

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

<template>
  <div class="recorrido" :class="{ 'recorrido--vertical': vertical || $q.screen.lt.sm }">
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

<style scoped lang="scss">
.recorrido {
  display: flex;
  align-items: center;
  overflow-x: auto;
  padding: 12px 4px 16px;

  .parada {
    flex: 0 0 auto;
    animation: entrar-desde-izquierda $duracion-media $curva-salida backwards;

    @for $i from 1 through 8 {
      &:nth-of-type(#{$i}) {
        animation-delay: #{($i - 1) * 0.08}s;
      }
    }
  }

  &__enlace {
    flex: 1 1 32px;
    min-width: 20px;
    height: 3px;
    background: repeating-linear-gradient(90deg, $acero-claro 0 3px, transparent 3px 9px);

    &--terminado {
      background: $positive;
    }

    &--en_curso {
      background: repeating-linear-gradient(90deg, $accent 0 7px, transparent 7px 16px);
      background-size: 16px 3px;
      animation: fluir-horizontal 0.9s linear infinite;
    }
  }

  &--vertical {
    flex-direction: column;
    align-items: stretch;
    overflow-x: visible;

    .parada {
      width: auto;
    }

    .recorrido__enlace {
      flex: 0 0 28px;
      width: 3px;
      height: 28px;
      margin-left: 28px;
      background: repeating-linear-gradient(180deg, $acero-claro 0 3px, transparent 3px 9px);

      &--terminado {
        background: $positive;
      }

      &--en_curso {
        background: repeating-linear-gradient(180deg, $accent 0 7px, transparent 7px 16px);
        background-size: 3px 16px;
        animation: fluir-vertical 0.9s linear infinite;
      }
    }
  }
}
</style>
