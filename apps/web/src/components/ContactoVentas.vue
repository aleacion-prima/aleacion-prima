<script setup lang="ts">
import { onMounted, ref } from 'vue';

interface Contacto {
  correo: string;
  telefono: string;
  horario: string;
}

const contacto = ref<Contacto | null>(null);
const error = ref(false);

onMounted(async () => {
  try {
    const respuesta = await fetch(`${import.meta.env.PUBLIC_API_URL}/api/publico/contacto`);
    if (!respuesta.ok) throw new Error('No se pudo cargar el contacto');
    contacto.value = (await respuesta.json()) as Contacto;
  } catch {
    error.value = true;
  }
});
</script>

<template>
  <div class="contacto" aria-live="polite">
    <p v-if="!contacto && !error">Cargando datos de contacto…</p>
    <p v-else-if="error">No pudimos cargar los datos de contacto. Intenta de nuevo más tarde.</p>
    <dl v-else>
      <div>
        <dt>Correo</dt>
        <dd>
          <a :href="`mailto:${contacto?.correo}`">{{ contacto?.correo }}</a>
        </dd>
      </div>
      <div>
        <dt>Teléfono</dt>
        <dd>
          <a :href="`tel:${contacto?.telefono}`">{{ contacto?.telefono }}</a>
        </dd>
      </div>
      <div>
        <dt>Horario de atención</dt>
        <dd>{{ contacto?.horario }}</dd>
      </div>
    </dl>
  </div>
</template>

<style scoped>
.contacto {
  background: #f2f6f8;
  border-radius: 0.5rem;
  padding: 1.5rem;
}

dl {
  display: grid;
  gap: 1rem;
  margin: 0;
}

dt {
  color: #5d6873;
  font-size: 0.85rem;
  font-weight: 700;
}

dd {
  margin: 0.25rem 0 0;
}
</style>
