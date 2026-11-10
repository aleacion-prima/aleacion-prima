import { mount } from '@vue/test-utils';
import { Quasar } from 'quasar';
import { describe, expect, it } from 'vitest';
import { createMemoryHistory, createRouter } from 'vue-router';
import MenuLateral from './MenuLateral.vue';

const montar = (rol: 'administrador' | 'encargado', modulos: Record<string, boolean> = {}) => {
  const router = createRouter({
    history: createMemoryHistory(),
    routes: [{ path: '/:todo(.*)*', component: { template: '<div />' } }],
  });
  return mount(MenuLateral, { props: { rol, modulos }, global: { plugins: [Quasar, router] } });
};

describe('MenuLateral', () => {
  it('muestra los catálogos al administrador', () => {
    const texto = montar('administrador', { ventas: true }).text();

    expect(texto).toContain('Catálogos');
    expect(texto).toContain('Usuarios y roles');
    expect(texto).toContain('Cotizaciones');
  });

  it('oculta la sección de ventas cuando el módulo está apagado', () => {
    const texto = montar('administrador', { ventas: false }).text();

    expect(texto).toContain('Catálogos');
    expect(texto).not.toContain('Ventas');
    expect(texto).not.toContain('Clientes');
    expect(texto).not.toContain('Cotizaciones');
  });

  it('al encargado solo le muestra su área, lotes y notificaciones', () => {
    const wrapper = montar('encargado');

    expect(wrapper.text()).toContain('Mi área');
    expect(wrapper.text()).not.toContain('Catálogos');
    expect(wrapper.findAll('.q-item')).toHaveLength(3);
  });
});
