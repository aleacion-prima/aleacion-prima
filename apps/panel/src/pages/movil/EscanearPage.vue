<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue';
import { useQuasar } from 'quasar';
import { useRouter } from 'vue-router';
import { esCodigoLote, normalizarCodigo } from '@/composables/codigoLote';
import { detenerEscaneo, iniciarEscaneo, permisoCamara } from '@/services/escaner';

type Estado = 'preparando' | 'escaneando' | 'sin-permiso' | 'sin-camara';

const $q = useQuasar();
const router = useRouter();
const estado = ref<Estado>('preparando');

let ultimoAjeno = '';
let leido = false;

const detener = async (): Promise<void> => {
  document.body.classList.remove('escaneando');
  await detenerEscaneo().catch(() => undefined);
};

const alLeer = (texto: string): void => {
  if (leido) return;
  if (!esCodigoLote(texto)) {
    if (texto === ultimoAjeno) return;
    ultimoAjeno = texto;
    $q.notify({ type: 'warning', message: 'Ese QR no es de un lote', position: 'top' });
    return;
  }
  leido = true;
  void detener().then(() => router.replace(`/lotes/${normalizarCodigo(texto)}`));
};

const cerrar = async (): Promise<void> => {
  await detener();
  await router.replace('/');
};

onMounted(async () => {
  try {
    if (!(await permisoCamara())) {
      estado.value = 'sin-permiso';
      return;
    }
    document.body.classList.add('escaneando');
    await iniciarEscaneo(alLeer);
    estado.value = 'escaneando';
  } catch {
    await detener();
    estado.value = 'sin-camara';
  }
});

onUnmounted(() => {
  void detener();
});
</script>

<template>
  <q-page class="escanear" :class="{ 'escanear--activo': estado === 'escaneando' }">
    <div class="escanear__controles">
      <q-btn
        round
        flat
        icon="close"
        class="escanear__control"
        aria-label="Cerrar"
        @click="cerrar"
      />
    </div>

    <div v-if="estado === 'escaneando' || estado === 'preparando'" class="escanear__visor">
      <div class="escanear__marco">
        <div class="escanear__linea" />
      </div>
      <p class="escanear__ayuda">Apunta al código QR del lote</p>
    </div>

    <div v-else class="escanear__visor">
      <q-icon :name="estado === 'sin-permiso' ? 'no_photography' : 'videocam_off'" size="48px" />
      <p class="escanear__ayuda">
        {{
          estado === 'sin-permiso'
            ? 'Sin permiso para usar la cámara'
            : 'No se pudo abrir la cámara'
        }}
      </p>
      <q-btn unelevated no-caps color="primary" label="Escribir el código" @click="cerrar" />
    </div>
  </q-page>
</template>

<style scoped lang="scss">
.escanear {
  display: flex;
  flex-direction: column;
  gap: 16px;
  padding: 24px;
  background: $dark;
  color: white;
}

.escanear--activo {
  background: transparent;
}

.escanear__controles {
  display: flex;
  justify-content: space-between;
}

.escanear__control {
  background: rgba(white, 0.15);
}

.escanear__visor {
  display: flex;
  flex: 1;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 16px;
}

.escanear__marco {
  position: relative;
  width: 220px;
  height: 220px;
  border: 3px solid white;
  border-radius: 16px;
  box-shadow: 0 0 0 100vmax rgba($dark, 0.55);
}

.escanear__linea {
  position: absolute;
  left: 13px;
  right: 13px;
  top: 50%;
  height: 2px;
  background: $accent;
  animation: barrer 2s ease-in-out infinite alternate;
}

.escanear__ayuda {
  margin: 0;
  font-size: 14px;
  line-height: 20px;
  color: $acero-claro;
  text-align: center;
}

@keyframes barrer {
  from {
    top: 12%;
  }
  to {
    top: 88%;
  }
}

@media (prefers-reduced-motion: reduce) {
  .escanear__linea {
    animation: none;
  }
}
</style>

<style lang="scss">
body.escaneando,
body.escaneando .q-layout,
body.escaneando .q-page-container {
  background: transparent !important;
}
</style>
