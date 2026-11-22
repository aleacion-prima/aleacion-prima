<script setup lang="ts">
import { PUBLIC_API_URL } from 'astro:env/client';
import { onMounted, ref } from 'vue';

interface Contacto {
  correo: string;
  telefono: string;
  horario: string;
}

const contacto = ref<Contacto | null>(null);
const cargando = ref(true);
const error = ref(false);

const cargar = async () => {
  cargando.value = true;
  error.value = false;

  try {
    const respuesta = await fetch(`${PUBLIC_API_URL}/api/publico/contacto`, {
      headers: { Accept: 'application/json' },
    });
    if (!respuesta.ok) throw new Error('No se pudo cargar el contacto');
    contacto.value = (await respuesta.json()) as Contacto;
  } catch {
    contacto.value = null;
    error.value = true;
  } finally {
    cargando.value = false;
  }
};

onMounted(cargar);
</script>

<template>
  <article class="contacto" aria-live="polite">
    <h2>Contacto de ventas</h2>

    <p v-if="cargando" class="estado">Cargando datos de contacto…</p>

    <div v-else-if="error" class="estado">
      <p>No pudimos cargar los datos de contacto. Intenta de nuevo más tarde.</p>
      <button type="button" class="reintentar" @click="cargar">Reintentar</button>
    </div>

    <dl v-else-if="contacto" class="filas">
      <div class="fila">
        <dt>Correo</dt>
        <dd>
          <a :href="`mailto:${contacto.correo}`">{{ contacto.correo }}</a>
        </dd>
      </div>
      <div class="fila">
        <dt>Teléfono</dt>
        <dd>
          <a :href="`tel:${contacto.telefono.replace(/[^\d+]/g, '')}`">{{ contacto.telefono }}</a>
        </dd>
      </div>
      <div class="fila">
        <dt>Horario</dt>
        <dd>{{ contacto.horario }}</dd>
      </div>
    </dl>
  </article>
</template>

<style scoped>
.contacto {
  background: var(--ap-superficie);
  border: 1px solid var(--ap-borde);
  border-radius: 12px;
  margin: 0 auto;
  max-width: 560px;
  padding: 24px;
}

h2 {
  font-size: 20px;
  font-weight: 600;
  line-height: 24px;
  margin-bottom: 20px;
}

.filas {
  display: grid;
  gap: 12px;
  margin: 0;
}

.fila {
  align-items: center;
  border: 1px solid var(--ap-borde);
  border-radius: 8px;
  display: flex;
  flex-wrap: wrap;
  gap: 8px 16px;
  justify-content: space-between;
  margin: 0;
  padding: 12px 16px;
}

dt {
  color: var(--ap-texto-secundario);
  font-size: 12px;
  font-weight: 500;
  line-height: 16px;
  margin: 0;
}

dd {
  color: var(--q-dark);
  font-family: var(--fuente-codigo);
  font-size: 13px;
  font-weight: 500;
  line-height: 20px;
  margin: 0;
  overflow-wrap: anywhere;
  text-align: right;
}

dd a {
  color: var(--q-primary);
  text-decoration: none;
}

dd a:hover {
  text-decoration: underline;
}

.estado {
  color: var(--ap-texto-secundario);
  margin: 0;
}

.estado p {
  margin: 0;
}

.reintentar {
  background: var(--q-primary);
  border: 0;
  border-radius: 8px;
  color: var(--ap-superficie);
  cursor: pointer;
  font-family: var(--fuente-cuerpo);
  font-size: 14px;
  font-weight: 500;
  line-height: 20px;
  margin-top: 12px;
  padding: 8px 16px;
}

@media (min-width: 640px) {
  .contacto {
    padding: 32px;
  }
}
</style>
