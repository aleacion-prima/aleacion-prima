import { mount } from '@vue/test-utils';
import { Quasar } from 'quasar';
import { describe, expect, it } from 'vitest';
import type { Area } from '@aleacion-prima/types';
import EditorRecorrido from './EditorRecorrido.vue';

const area = (id: number, codigo: string, nombre: string, obligatoria: boolean): Area => ({
  id,
  codigo,
  nombre,
  descripcion: null,
  tarifa_mano_obra_hora: '180.0000',
  costo_maquina_hora: '220.0000',
  obligatoria,
  orden_catalogo: id,
  activo: true,
});

const areas = [
  area(1, 'MOL', 'Moldeo', true),
  area(2, 'FUS', 'Fusión', true),
  area(3, 'GRA', 'Granallado', false),
  area(4, 'CAL', 'Calidad', false),
];

const montar = (modelValue: number[]) =>
  mount(EditorRecorrido, { props: { modelValue, areas }, global: { plugins: [Quasar] } });

describe('EditorRecorrido', () => {
  it('solo deja quitar las paradas opcionales', () => {
    const wrapper = montar([1, 2, 3]);

    expect(wrapper.findAll('.editor__quitar')).toHaveLength(1);
  });

  it('quita una parada opcional', async () => {
    const wrapper = montar([1, 2, 3]);

    await wrapper.find('.editor__quitar').trigger('click');

    expect(wrapper.emitted('update:modelValue')?.[0]).toEqual([[1, 2]]);
  });

  it('ofrece agregar las opcionales que faltan', async () => {
    const wrapper = montar([1, 2, 3]);
    const boton = wrapper.findAll('button').find((b) => b.text().includes('Calidad'));

    await boton?.trigger('click');

    expect(wrapper.emitted('update:modelValue')?.[0]).toEqual([[1, 2, 3, 4]]);
  });

  it('permite quitar una obligatoria repetida', () => {
    const wrapper = montar([1, 2, 1]);

    expect(wrapper.findAll('.editor__quitar')).toHaveLength(2);
  });

  it('reordena en vivo al arrastrar una opcional y avisa al soltar', async () => {
    const wrapper = montar([1, 2, 3]);
    const paradas = wrapper.findAll('.editor__parada');

    await paradas[2]?.trigger('dragstart');
    await paradas[1]?.trigger('dragenter');

    expect(wrapper.findAll('.editor__parada .codigo').map((c) => c.text())).toEqual([
      'MOL',
      'GRA',
      'FUS',
    ]);
    expect(wrapper.emitted('update:modelValue')).toBeUndefined();

    await wrapper.findAll('.editor__parada')[1]?.trigger('dragend');

    expect(wrapper.emitted('update:modelValue')?.[0]).toEqual([[1, 3, 2]]);
  });

  it('no deja arrastrar las obligatorias', () => {
    const wrapper = montar([1, 2, 3]);

    expect(wrapper.findAll('.editor__parada').map((p) => p.attributes('draggable'))).toEqual([
      'false',
      'false',
      'true',
    ]);
  });
});
