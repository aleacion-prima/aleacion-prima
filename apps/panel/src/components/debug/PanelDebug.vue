<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { useQuasar } from 'quasar';
import { useRouter } from 'vue-router';
import type { Usuario } from '@aleacion-prima/types';
import {
  alternarAreas,
  avanzarLote,
  crearLote,
  crearVariosLotes,
  entrarComo,
  listarUsuarios,
  reiniciarBd,
  rellenarInventario,
  type Avance,
} from '@/composables/modoDebug';

const props = defineProps<{ codigo: string | null; usuarioActual: number | null }>();
const emit = defineEmits<{ actualizado: [] }>();

const $q = useQuasar();
const router = useRouter();
const usuarios = ref<Usuario[]>([]);
const ocupado = ref(false);

const actual = computed(() => usuarios.value.find((usuario) => usuario.id === props.usuarioActual));

const avances: { etiqueta: string; avance: Avance }[] = [
  { etiqueta: 'Avanzar 1', avance: {} },
  { etiqueta: 'Al final', avance: { hasta: 'final' } },
  { etiqueta: '+ merma', avance: { merma: 1 } },
  { etiqueta: '+ faltante', avance: { faltante: 1 } },
];

const ejecutar = async (accion: () => Promise<void>): Promise<void> => {
  ocupado.value = true;
  try {
    await accion();
  } catch (e) {
    $q.notify({ type: 'negative', message: e instanceof Error ? e.message : 'El truco falló' });
  } finally {
    ocupado.value = false;
  }
};

const recargarEn = (ruta: string): void => {
  window.location.hash = `#${ruta}`;
  window.location.reload();
};

const cambiarA = (usuario: Usuario): Promise<void> =>
  ejecutar(async () => {
    await entrarComo(usuario.id);
    recargarEn('/');
  });

const nuevoLote = (): Promise<void> =>
  ejecutar(async () => {
    const lote = await crearLote();
    await router.push(`/lotes/${lote.codigo}`);
  });

const variosLotes = (): Promise<void> =>
  ejecutar(async () => {
    await crearVariosLotes(5);
    await router.push('/lotes');
    emit('actualizado');
  });

const rellenar = (): Promise<void> =>
  ejecutar(async () => {
    await rellenarInventario();
    $q.notify({ type: 'positive', message: 'Inventario rellenado' });
    emit('actualizado');
  });

const areas = (): Promise<void> =>
  ejecutar(async () => {
    await alternarAreas();
    window.location.reload();
  });

const avanzar = (avance: Avance): Promise<void> =>
  ejecutar(async () => {
    if (!props.codigo) return;
    await avanzarLote(props.codigo, avance);
    emit('actualizado');
  });

const reiniciar = (): void => {
  $q.dialog({
    title: 'Reiniciar BD',
    message: 'Se borran todos los datos locales.',
    cancel: { flat: true, noCaps: true, label: 'Cancelar' },
    ok: { unelevated: true, noCaps: true, color: 'negative', label: 'Reiniciar' },
  }).onOk(() => {
    void ejecutar(async () => {
      await reiniciarBd();
      recargarEn('/login');
    });
  });
};

onMounted(() =>
  ejecutar(async () => {
    usuarios.value = await listarUsuarios();
  }),
);
</script>

<template>
  <q-card flat class="panel-debug">
    <q-card-section class="q-pb-xs">
      <div class="text-overline text-secundario">Entrar como</div>
    </q-card-section>
    <q-list dense>
      <q-item
        v-for="usuario in usuarios"
        :key="usuario.id"
        clickable
        :disable="ocupado"
        :active="usuario.id === usuarioActual"
        @click="cambiarA(usuario)"
      >
        <q-item-section>
          <q-item-label>{{ usuario.name }}</q-item-label>
          <q-item-label caption>
            {{ usuario.rol
            }}<template v-if="usuario.areas?.length">
              · {{ usuario.areas.map((area) => area.codigo).join(', ') }}</template
            >
          </q-item-label>
        </q-item-section>
      </q-item>
    </q-list>

    <template v-if="codigo">
      <q-separator class="q-my-sm" />
      <q-card-section class="q-py-xs">
        <div class="text-overline text-secundario">
          Este lote · <span class="codigo">{{ codigo }}</span>
        </div>
        <div class="panel-debug__avances q-mt-xs">
          <q-btn
            v-for="opcion in avances"
            :key="opcion.etiqueta"
            outline
            dense
            no-caps
            color="primary"
            :label="opcion.etiqueta"
            :disable="ocupado"
            @click="avanzar(opcion.avance)"
          />
        </div>
      </q-card-section>
    </template>

    <q-separator class="q-my-sm" />
    <q-card-actions vertical align="left" class="q-pt-none">
      <q-btn
        flat
        no-caps
        align="left"
        icon="add"
        label="Crear lote de prueba"
        :disable="ocupado"
        @click="nuevoLote"
      />
      <q-btn
        flat
        no-caps
        align="left"
        icon="library_add"
        label="Crear 5 lotes repartidos"
        :disable="ocupado"
        @click="variosLotes"
      />
      <q-btn
        flat
        no-caps
        align="left"
        icon="inventory_2"
        label="Rellenar inventario"
        :disable="ocupado"
        @click="rellenar"
      />
      <q-btn
        v-if="actual?.rol === 'encargado'"
        flat
        no-caps
        align="left"
        icon="badge"
        label="Alternar todas mis áreas"
        :disable="ocupado"
        @click="areas"
      />
      <q-btn
        flat
        no-caps
        align="left"
        color="negative"
        icon="restart_alt"
        label="Reiniciar BD"
        :disable="ocupado"
        @click="reiniciar"
      />
    </q-card-actions>
  </q-card>
</template>

<style scoped>
.panel-debug {
  width: 288px;
}

.panel-debug__avances {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 8px;
}
</style>
