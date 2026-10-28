import { mount } from '@vue/test-utils';
import { Quasar } from 'quasar';
import { describe, expect, it } from 'vitest';
import { createMemoryHistory, createRouter } from 'vue-router';
import BarraSuperior from './BarraSuperior.vue';

const montar = (pendientes = 0) => {
  const router = createRouter({
    history: createMemoryHistory(),
    routes: [{ path: '/:todo(.*)*', component: { template: '<div />' } }],
  });
  return mount(BarraSuperior, {
    props: { nombre: 'Juan', rol: 'ventas', pendientes },
    global: {
      plugins: [Quasar, router],
      stubs: { 'q-header': { template: '<div><slot /></div>' } },
    },
  });
};

describe('BarraSuperior', () => {
  it('muestra el nombre y la etiqueta del rol', () => {
    const texto = montar().text();

    expect(texto).toContain('Juan');
    expect(texto).toContain('Ventas');
  });

  it('muestra el contador solo cuando hay pendientes', () => {
    expect(montar().find('.q-badge').exists()).toBe(false);
    expect(montar(3).find('.q-badge').text()).toBe('3');
  });

  it('avisa al layout cuando se pulsa el menú', async () => {
    const wrapper = montar();
    await wrapper.find('button[aria-label="Menú"]').trigger('click');

    expect(wrapper.emitted('alternar-menu')).toHaveLength(1);
  });
});
