import { mount } from '@vue/test-utils';
import { Quasar } from 'quasar';
import { describe, expect, it } from 'vitest';
import ParadaRecorrido from './ParadaRecorrido.vue';

const montar = (props: Partial<InstanceType<typeof ParadaRecorrido>['$props']> = {}) =>
  mount(ParadaRecorrido, {
    props: { orden: 1, codigo: 'MOL', nombre: 'Moldeo', estado: 'terminada', ...props },
    global: { plugins: [Quasar] },
  });

describe('ParadaRecorrido', () => {
  it('muestra orden, código, nombre y detalle', () => {
    const wrapper = montar({ tiempo: '07:00 a 10:00 · 3.0 h', piezas: '20 pzas entregadas' });

    expect(wrapper.text()).toContain('1');
    expect(wrapper.text()).toContain('MOL');
    expect(wrapper.text()).toContain('Moldeo');
    expect(wrapper.text()).toContain('3.0 h');
    expect(wrapper.text()).toContain('20 pzas');
  });

  it('muestra el aviso solo cuando lo recibe', () => {
    expect(montar().find('.q-chip').exists()).toBe(false);
    expect(montar({ aviso: 'Falla de máquina · 40 min' }).text()).toContain('Falla de máquina');
  });

  it('marca con candado las obligatorias en el plan', () => {
    const wrapper = montar({ estado: 'planeada', obligatoria: true });

    expect(wrapper.find('.q-icon').text()).toBe('lock');
  });
});
