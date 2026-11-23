<script setup lang="ts">
const fijarAlSalir = (elemento: Element): void => {
  const tarjeta = elemento as HTMLElement;
  const { offsetLeft, offsetTop, offsetWidth, offsetHeight } = tarjeta;
  Object.assign(tarjeta.style, {
    position: 'absolute',
    left: `${offsetLeft}px`,
    top: `${offsetTop}px`,
    width: `${offsetWidth}px`,
    height: `${offsetHeight}px`,
  });
};
</script>

<template>
  <TransitionGroup
    tag="div"
    class="cuadricula-tarjetas"
    name="tarjeta"
    appear
    appear-active-class="tarjeta-aparecer"
    @before-leave="fijarAlSalir"
  >
    <slot />
  </TransitionGroup>
</template>

<style scoped>
.cuadricula-tarjetas {
  position: relative;
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 24px;
}

@media (max-width: 1023px) {
  .cuadricula-tarjetas {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 599px) {
  .cuadricula-tarjetas {
    grid-template-columns: 1fr;
  }
}
</style>

<style>
@media (prefers-reduced-motion: no-preference) {
  .cuadricula-tarjetas > .tarjeta-aparecer {
    animation: tarjeta-entrar 0.6s cubic-bezier(0.2, 0.7, 0.2, 1) backwards;
    animation-delay: calc(var(--i, 0) * 80ms + 0.1s);
  }

  .cuadricula-tarjetas > .tarjeta-enter-active {
    animation: tarjeta-entrar 0.55s cubic-bezier(0.2, 0.7, 0.2, 1) 0.15s backwards;
  }

  .cuadricula-tarjetas > .tarjeta-leave-active {
    z-index: 0;
    animation: tarjeta-salir 0.35s ease-in forwards;
  }

  .cuadricula-tarjetas > .tarjeta.tarjeta-move {
    transition:
      transform 0.5s cubic-bezier(0.2, 0.7, 0.2, 1),
      border-color 0.2s ease,
      box-shadow 0.2s ease;
  }
}

@keyframes tarjeta-entrar {
  from {
    opacity: 0;
    transform: translateY(24px);
  }
}

@keyframes tarjeta-salir {
  to {
    opacity: 0;
    transform: scale(0.92);
  }
}
</style>
