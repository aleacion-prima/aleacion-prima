<template>
  <q-page padding class="column q-gutter-y-lg">
    <div class="text-h4">Muestrario</div>

    <section>
      <div class="text-h6 q-mb-sm">Chip de estado</div>
      <div class="row q-gutter-sm">
        <ChipEstado v-for="estado in ESTADOS_LOTE" :key="estado" :estado="estado" />
      </div>
    </section>

    <section>
      <div class="text-h6 q-mb-sm">Botones</div>
      <div class="row q-gutter-sm">
        <q-btn color="primary" unelevated label="Primario" icon="add" />
        <q-btn color="primary" outline label="Secundario" />
        <q-btn color="primary" flat label="Texto" />
        <q-btn color="primary" unelevated label="Deshabilitado" disable />
      </div>
    </section>

    <section>
      <div class="text-h6 q-mb-sm">Recorrido</div>
      <q-card flat bordered class="q-pa-md">
        <RecorridoLote :paradas="paradas" />
      </q-card>
    </section>

    <section>
      <div class="text-h6 q-mb-sm">Recorrido planeado</div>
      <q-card flat bordered class="q-pa-md">
        <RecorridoLote :paradas="plan" />
      </q-card>
    </section>

    <section>
      <div class="text-h6 q-mb-sm">Pasos de recorrido</div>
      <div class="row items-center q-gutter-xs">
        <PasoRecorrido v-for="p in paradas" :key="p.codigo" :area="p.codigo" :estado="p.estado" />
        <span class="codigo q-ml-md">LOT-20260928-01</span>
      </div>
    </section>

    <section>
      <div class="text-h6 q-mb-sm">Estado vacío</div>
      <q-card flat bordered>
        <EstadoVacio titulo="Sin lotes en proceso" descripcion="Crea el primero para verlo aquí">
          <q-btn color="primary" unelevated label="Nuevo lote" icon="add" />
        </EstadoVacio>
      </q-card>
    </section>

    <section>
      <div class="text-h6 q-mb-sm">Avisos</div>
      <div class="column q-gutter-y-sm" style="max-width: 560px">
        <q-banner dense class="bg-info text-white" inline-actions>Lote creado</q-banner>
        <q-banner dense class="bg-positive text-white">Cierre confirmado</q-banner>
        <q-banner dense class="bg-warning text-white">Captura tardía</q-banner>
        <q-banner dense class="bg-negative text-white">No se pudo guardar</q-banner>
      </div>
    </section>
  </q-page>
</template>

<script setup lang="ts">
import { ESTADOS_LOTE } from '@aleacion-prima/types';
import type { ParadaLote } from '@/catalogos/areas';
import ChipEstado from '@/components/lotes/ChipEstado.vue';
import EstadoVacio from '@/components/comunes/EstadoVacio.vue';
import PasoRecorrido from '@/components/lotes/PasoRecorrido.vue';
import RecorridoLote from '@/components/lotes/RecorridoLote.vue';

const paradas: ParadaLote[] = [
  {
    codigo: 'MOL',
    nombre: 'Moldeo',
    estado: 'terminada',
    tiempo: '07:00 a 10:00 · 3.0 h',
    piezas: '20 pzas entregadas',
  },
  {
    codigo: 'FUS',
    nombre: 'Fusión',
    estado: 'terminada',
    tiempo: '10:00 a 12:30 · 2.5 h',
    piezas: '20 pzas entregadas',
    aviso: 'Falla de máquina · 40 min',
  },
  {
    codigo: 'COL',
    nombre: 'Colada',
    estado: 'terminada',
    tiempo: '12:30 a 17:30 · 5.0 h',
    piezas: '20 pzas entregadas',
  },
  {
    codigo: 'DES',
    nombre: 'Desmolde',
    estado: 'en_curso',
    tiempo: 'Desde 29 sep 07:00',
    piezas: '20 pzas recibidas',
  },
  { codigo: 'GRA', nombre: 'Granallado', estado: 'pendiente', tiempo: 'Pendiente' },
  { codigo: 'CAL', nombre: 'Calidad', estado: 'pendiente', tiempo: 'Pendiente' },
];

const plan: ParadaLote[] = paradas.map((p) => ({
  codigo: p.codigo,
  nombre: p.nombre,
  estado: 'planeada',
  obligatoria: ['MOL', 'FUS', 'COL', 'DES'].includes(p.codigo),
}));
</script>
