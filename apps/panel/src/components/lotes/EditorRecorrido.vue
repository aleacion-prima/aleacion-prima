<script setup lang="ts">
import { computed, nextTick, ref, watch } from 'vue';
import type { Area } from '@aleacion-prima/types';
import { formatearMoneda } from '@/composables/formato';
import ParadaRecorrido from './ParadaRecorrido.vue';

interface Item {
  clave: number;
  id: number;
}

const props = defineProps<{ modelValue: number[]; areas: Area[] }>();
const emit = defineEmits<{ 'update:modelValue': [ids: number[]] }>();

let siguienteClave = 0;
const items = ref<Item[]>([]);
const arrastrada = ref<number | null>(null);
const recienSoltada = ref<number | null>(null);
const sacudida = ref<number | null>(null);
const contenedor = ref<HTMLElement | null>(null);
let inicioPresion: { clave: number; x: number } | null = null;

const porId = computed(() => new Map(props.areas.map((area) => [area.id, area])));

const visibles = computed(() =>
  items.value
    .map((item) => ({ ...item, area: porId.value.get(item.id) }))
    .filter((item): item is Item & { area: Area } => item.area !== undefined),
);

const idsActuales = (): number[] => items.value.map((item) => item.id);

const sincronizar = (ids: number[]): void => {
  const disponibles = [...items.value];
  items.value = ids.map((id) => {
    const indice = disponibles.findIndex((item) => item.id === id);
    const existente = indice >= 0 ? disponibles.splice(indice, 1)[0] : undefined;
    return existente ?? { clave: siguienteClave++, id };
  });
};

watch(
  () => props.modelValue,
  (ids) => {
    if (ids.join(',') !== idsActuales().join(',')) sincronizar(ids);
  },
  { immediate: true },
);

const emitir = (): void => emit('update:modelValue', idsActuales());

const opcionalesFaltantes = computed(() =>
  props.areas.filter((area) => !area.obligatoria && !props.modelValue.includes(area.id)),
);

const enRecorrido = computed(() =>
  props.areas.filter((area) => props.modelValue.includes(area.id)),
);

const veces = (id: number): number => items.value.filter((item) => item.id === id).length;

const sePuedeQuitar = (area: Area): boolean => !area.obligatoria || veces(area.id) > 1;

const tarifas = (area: Area): string =>
  `MO ${formatearMoneda(area.tarifa_mano_obra_hora)}/h · Máq. ${formatearMoneda(area.costo_maquina_hora)}/h`;

const marcar = (destino: typeof recienSoltada, clave: number): void => {
  destino.value = null;
  void nextTick(() => {
    destino.value = clave;
    setTimeout(() => {
      if (destino.value === clave) destino.value = null;
    }, 450);
  });
};

const agregar = (id: number): void => {
  items.value = [...items.value, { clave: siguienteClave++, id }];
  emitir();
  void nextTick(() => {
    if (contenedor.value) contenedor.value.scrollLeft = contenedor.value.scrollWidth;
  });
};

const quitar = (clave: number): void => {
  items.value = items.value.filter((item) => item.clave !== clave);
  emitir();
};

const alIniciar = (evento: DragEvent, clave: number): void => {
  arrastrada.value = clave;
  if (evento.dataTransfer) {
    evento.dataTransfer.effectAllowed = 'move';
    evento.dataTransfer.setData('text/plain', String(clave));
  }
};

const alEntrar = (clave: number): void => {
  if (arrastrada.value === null || arrastrada.value === clave) return;

  const origen = items.value.findIndex((item) => item.clave === arrastrada.value);
  const destino = items.value.findIndex((item) => item.clave === clave);
  if (origen < 0 || destino < 0) return;

  const reordenados = [...items.value];
  const [movido] = reordenados.splice(origen, 1);
  if (!movido) return;
  reordenados.splice(destino, 0, movido);
  items.value = reordenados;
};

const alTerminar = (): void => {
  const clave = arrastrada.value;
  if (clave === null) return;
  arrastrada.value = null;
  marcar(recienSoltada, clave);
  emitir();
};

const alPresionar = (evento: PointerEvent, clave: number): void => {
  inicioPresion = { clave, x: evento.clientX };
};

const alMover = (evento: PointerEvent): void => {
  if (!inicioPresion || evento.buttons === 0) return;
  if (Math.abs(evento.clientX - inicioPresion.x) > 6) {
    marcar(sacudida, inicioPresion.clave);
    inicioPresion = null;
  }
};

const alSoltarPresion = (): void => {
  inicioPresion = null;
};
</script>

<template>
  <div>
    <div ref="contenedor" class="editor">
      <TransitionGroup
        tag="div"
        name="parada"
        appear
        class="row no-wrap items-start"
        @dragover.prevent
        @drop.prevent="alTerminar"
      >
        <div
          v-for="(item, posicion) in visibles"
          :key="item.clave"
          class="editor__parada"
          :class="{
            'editor__parada--movible': !item.area.obligatoria,
            'editor__parada--arrastrada': arrastrada === item.clave,
            rebotar: recienSoltada === item.clave,
            sacudir: sacudida === item.clave,
          }"
          :draggable="!item.area.obligatoria"
          @dragstart="alIniciar($event, item.clave)"
          @dragenter.prevent="alEntrar(item.clave)"
          @dragend="alTerminar"
          @pointerdown="item.area.obligatoria && alPresionar($event, item.clave)"
          @pointermove="alMover"
          @pointerup="alSoltarPresion"
          @pointerleave="alSoltarPresion"
        >
          <ParadaRecorrido
            :orden="posicion + 1"
            :codigo="item.area.codigo"
            :nombre="item.area.nombre"
            estado="planeada"
            :obligatoria="item.area.obligatoria"
            :tiempo="tarifas(item.area)"
          />
          <q-btn
            v-if="sePuedeQuitar(item.area)"
            round
            dense
            flat
            size="sm"
            icon="close"
            class="editor__quitar"
            :aria-label="`Quitar ${item.area.nombre}`"
            @click="quitar(item.clave)"
          />
        </div>
      </TransitionGroup>
    </div>

    <div class="row q-gutter-sm q-mt-md">
      <q-btn
        v-for="area in opcionalesFaltantes"
        :key="area.id"
        outline
        no-caps
        color="secondary"
        icon="add"
        :label="area.nombre"
        @click="agregar(area.id)"
      />
      <q-btn-dropdown
        v-if="enRecorrido.length"
        outline
        no-caps
        color="secondary"
        icon="add"
        label="Repetir una parada"
      >
        <q-list dense>
          <q-item
            v-for="area in enRecorrido"
            :key="area.id"
            v-close-popup
            clickable
            @click="agregar(area.id)"
          >
            <q-item-section>
              <span
                ><span class="codigo">{{ area.codigo }}</span> · {{ area.nombre }}</span
              >
            </q-item-section>
          </q-item>
        </q-list>
      </q-btn-dropdown>
    </div>
  </div>
</template>

<style scoped lang="scss">
.editor {
  position: relative;
  overflow-x: auto;
  scroll-behavior: smooth;
  padding: 14px 14px 16px 4px;

  &__parada {
    position: relative;
    flex: 0 0 auto;

    & + & {
      margin-left: 20px;

      &::before {
        content: '';
        position: absolute;
        left: -18px;
        top: 50%;
        width: 16px;
        height: 3px;
        background: repeating-linear-gradient(90deg, $acero-claro 0 3px, transparent 3px 9px);
      }
    }

    &--movible {
      cursor: grab;

      &:active {
        cursor: grabbing;
      }
    }

    &--arrastrada :deep(.parada) {
      opacity: 0.35;
      border-style: dashed;
      border-color: $accent;
      transform: scale(0.96);
      box-shadow: none;
    }
  }

  &__quitar {
    position: absolute;
    top: -10px;
    right: -10px;
    z-index: 1;
    background: white;
    box-shadow: 0 1px 3px rgba($dark, 0.2);
  }
}

.parada-move {
  transition: transform $duracion-media $curva-salida;
}

.parada-enter-active {
  transition:
    opacity $duracion-media $curva-salida,
    transform $duracion-media $curva-salida;
}

.parada-leave-active {
  position: absolute;
  transition:
    opacity $duracion-rapida ease-in,
    transform $duracion-rapida ease-in;
}

.parada-enter-from {
  opacity: 0;
  transform: translateY(8px) scale(0.94);
}

.parada-leave-to {
  opacity: 0;
  transform: scale(0.9);
}
</style>
