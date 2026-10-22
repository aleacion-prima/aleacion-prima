import { mount } from '@vue/test-utils';
import { Quasar } from 'quasar';
import { describe, expect, it } from 'vitest';
import type { ParadaLote } from '@/catalogos/areas';
import RecorridoLote from './RecorridoLote.vue';

const paradas: ParadaLote[] = [
  { codigo: 'MOL', nombre: 'Moldeo', estado: 'terminada' },
  { codigo: 'FUS', nombre: 'Fusión', estado: 'terminada' },
  { codigo: 'COL', nombre: 'Colada', estado: 'en_curso' },
  { codigo: 'DES', nombre: 'Desmolde', estado: 'pendiente' },
];

describe('RecorridoLote', () => {
  it('numera las paradas en orden', () => {
    const wrapper = mount(RecorridoLote, { props: { paradas }, global: { plugins: [Quasar] } });

    expect(wrapper.findAll('.parada')).toHaveLength(4);
    expect(wrapper.findAll('.q-badge').map((b) => b.text())).toEqual(['1', '2', '3', '4']);
  });

  it('colorea los enlaces según las paradas que une', () => {
    const wrapper = mount(RecorridoLote, { props: { paradas }, global: { plugins: [Quasar] } });
    const enlaces = wrapper.findAll('.recorrido__enlace');

    expect(enlaces[0]?.classes()).toContain('recorrido__enlace--terminado');
    expect(enlaces[1]?.classes()).toContain('recorrido__enlace--en_curso');
    expect(enlaces[2]?.classes()).toContain('recorrido__enlace--pendiente');
  });
});
