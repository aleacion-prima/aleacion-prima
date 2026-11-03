import { mount } from '@vue/test-utils';
import { Quasar } from 'quasar';
import { describe, expect, it } from 'vitest';
import { createMemoryHistory, createRouter } from 'vue-router';
import MenuLateral from './MenuLateral.vue';

const montar = (rol: 'administrador' | 'encargado') => {
  const router = createRouter({
    history: createMemoryHistory(),
    routes: [{ path: '/:todo(.*)*', component: { template: '<div />' } }],
  });
  return mount(MenuLateral, { props: { rol }, global: { plugins: [Quasar, router] } });
};

describe('MenuLateral', () => {
  it('muestra los catálogos al administrador', () => {
    const texto = montar('administrador').text();

    expect(texto).toContain('Catálogos');
    expect(texto).toContain('Usuarios y roles');
    expect(texto).toContain('Cotizaciones');
  });

  it('al encargado solo le muestra su área, lotes y notificaciones', () => {
    const wrapper = montar('encargado');

    expect(wrapper.text()).toContain('Mi área');
    expect(wrapper.text()).not.toContain('Catálogos');
    expect(wrapper.findAll('.q-item')).toHaveLength(3);
  });
});
