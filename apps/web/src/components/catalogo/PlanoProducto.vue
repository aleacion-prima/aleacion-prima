<script setup lang="ts">
import { computed } from 'vue';
import type { ProductoPublico } from '@aleacion-prima/types';
import { material } from '../../composables/especificaciones';

const props = defineProps<{ producto: ProductoPublico }>();

const diametro = computed(() => {
  const valor = Number(props.producto.especificaciones?.diametro_mm);
  return Number.isFinite(valor) && valor > 0 ? valor : null;
});

const peso = computed(() => {
  const valor = props.producto.especificaciones?.peso_kg;
  return valor === undefined ? '' : `${valor} kg`;
});
</script>

<template>
  <figure class="plano-producto">
    <svg viewBox="0 0 480 360" role="img" :aria-label="`Plano de ${producto.nombre}`">
      <template v-if="diametro">
        <g class="plano-producto__ejes">
          <line x1="70" y1="160" x2="330" y2="160" />
          <line x1="200" y1="30" x2="200" y2="290" />
        </g>
        <circle class="plano-producto__pieza" cx="200" cy="160" r="112" />
        <circle class="plano-producto__trazo" cx="200" cy="160" r="112" pathLength="1" />
        <g class="plano-producto__cota">
          <line x1="88" y1="168" x2="88" y2="312" />
          <line x1="312" y1="168" x2="312" y2="312" />
          <line x1="88" y1="304" x2="312" y2="304" />
          <path d="M88 304l10-4v8Z M312 304l-10-4v8Z" />
          <text x="200" y="296" text-anchor="middle">Ø {{ diametro }}</text>
        </g>
      </template>
      <image
        v-else
        class="plano-producto__marca"
        href="/logo.svg"
        x="176"
        y="116"
        width="128"
        height="128"
      />

      <g class="plano-producto__rotulo">
        <rect x="216" y="316" width="256" height="36" />
        <text x="226" y="331" class="plano-producto__nombre">
          {{ producto.nombre.toUpperCase() }}
        </text>
        <text x="226" y="345">{{ material(producto) || 'Fundición en bruto' }}</text>
        <text v-if="peso" x="464" y="331" text-anchor="end">{{ peso }}</text>
      </g>
    </svg>
  </figure>
</template>

<style scoped>
.plano-producto {
  margin: 0;
  padding: 24px;
  background: #111a2e;
  border: 1px solid #1e293b;
  border-radius: 12px;
}

svg {
  display: block;
  width: 100%;
  height: auto;
}

.plano-producto__ejes line {
  stroke: #475569;
  stroke-width: 1;
  stroke-dasharray: 14 4 3 4;
}

.plano-producto__pieza {
  fill: #475569;
  fill-opacity: 0.45;
}

.plano-producto__trazo {
  fill: none;
  stroke: #e2e8f0;
  stroke-width: 1.6;
}

.plano-producto__cota line {
  stroke: #94a3b8;
  stroke-width: 1;
}

.plano-producto__cota path {
  fill: #94a3b8;
}

.plano-producto__marca {
  opacity: 0.5;
}

.plano-producto__rotulo rect {
  fill: none;
  stroke: #94a3b8;
  stroke-width: 1;
}

text {
  fill: #cbd5e1;
  font-family: var(--fuente-codigo);
  font-size: 10px;
  font-weight: 600;
}

.plano-producto__nombre {
  font-family: var(--fuente-titulos);
  font-size: 12px;
  font-weight: 700;
  fill: var(--ap-superficie);
}

@media (prefers-reduced-motion: no-preference) {
  .plano-producto__trazo {
    stroke-dasharray: 1;
    animation: trazar 1.2s ease-in-out 0.3s backwards;
  }

  .plano-producto__pieza {
    animation: colar 2.6s ease-out 1.3s backwards;
  }

  .plano-producto__ejes,
  .plano-producto__cota,
  .plano-producto__rotulo,
  .plano-producto__marca {
    animation: aparecer 0.6s ease 0.9s backwards;
  }
}

@keyframes trazar {
  from {
    stroke-dashoffset: 1;
  }

  to {
    stroke-dashoffset: 0;
  }
}

@keyframes colar {
  0% {
    fill: #fdba74;
    fill-opacity: 0;
  }

  20% {
    fill: #f97316;
    fill-opacity: 0.9;
  }

  100% {
    fill: #475569;
    fill-opacity: 0.45;
  }
}

@keyframes aparecer {
  from {
    opacity: 0;
  }
}
</style>
