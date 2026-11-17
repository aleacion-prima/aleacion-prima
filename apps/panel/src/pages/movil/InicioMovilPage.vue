<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { esCodigoLote, normalizarCodigo } from '@/composables/codigoLote';

const router = useRouter();
const codigo = ref('');

const reglaCodigo = (valor: string): true | string =>
  esCodigoLote(valor) || 'Usa el formato LOT-AAAAMMDD-NN';

const buscar = async (): Promise<void> => {
  await router.push(`/lotes/${normalizarCodigo(codigo.value)}`);
};
</script>

<template>
  <q-page class="inicio-movil">
    <q-btn
      unelevated
      no-caps
      color="primary"
      to="/escanear"
      class="boton-escanear"
      aria-label="Escanear etiqueta"
    >
      <div class="column items-center q-gutter-y-sm">
        <q-icon name="qr_code_scanner" size="48px" />
        <span class="boton-escanear__texto">Escanear etiqueta</span>
      </div>
    </q-btn>

    <q-form class="captura-manual" @submit="buscar">
      <q-input
        v-model="codigo"
        label="Código del lote"
        stack-label
        outlined
        dense
        bg-color="white"
        placeholder="LOT-20260928-01"
        autocapitalize="characters"
        autocomplete="off"
        input-class="codigo"
        :rules="[reglaCodigo]"
        lazy-rules
        class="col"
      />
      <q-btn
        type="submit"
        unelevated
        no-caps
        color="secondary"
        label="Buscar"
        class="boton-buscar"
      />
    </q-form>
  </q-page>
</template>

<style scoped lang="scss">
.inicio-movil {
  display: flex;
  flex-direction: column;
  gap: 16px;
  padding: 24px;
  background: $blanco-humo;
}

.boton-escanear {
  min-height: 120px;
  border-radius: 12px;
}

.boton-escanear__texto {
  font-family: $fuente-titulos;
  font-size: 20px;
  font-weight: 600;
  line-height: 24px;
}

.captura-manual {
  display: flex;
  align-items: flex-start;
  gap: 8px;
}

.boton-buscar {
  min-height: 40px;
}
</style>
