import { mount } from '@vue/test-utils';
import { Quasar } from 'quasar';
import { describe, expect, it } from 'vitest';
import { SEMAFOROS, type Semaforo } from '@aleacion-prima/types';
import PuntoSemaforo from './PuntoSemaforo.vue';

const montar = (semaforo: Semaforo | null, motivos: string[] = []) =>
  mount(PuntoSemaforo, { props: { semaforo, motivos }, global: { plugins: [Quasar] } });

describe('PuntoSemaforo', () => {
  it.each([
    ['verde', 'bg-positive'],
    ['ambar', 'bg-accent'],
    ['rojo', 'bg-negative'],
  ] as const)('pinta el %s con el color semántico %s', (semaforo, clase) => {
    const punto = montar(semaforo).find('.punto-semaforo');

    expect(punto.classes()).toContain(clase);
    expect(punto.attributes('data-semaforo')).toBe(semaforo);
  });

  it.each(SEMAFOROS)('el %s tiene nombre accesible', (semaforo) => {
    expect(montar(semaforo).find('.punto-semaforo').attributes('aria-label')).toContain('Semáforo');
  });

  it('el nombre accesible incluye los motivos', () => {
    const motivos = ['Merma acumulada de 5 de 100 piezas (5.0 %).'];

    const etiqueta = montar('ambar', motivos).find('.punto-semaforo').attributes('aria-label');

    expect(etiqueta).toContain('Semáforo ámbar');
    expect(etiqueta).toContain('Merma acumulada de 5 de 100 piezas');
  });

  it('sin semáforo (lote cerrado) no pinta nada', () => {
    expect(montar(null).find('.punto-semaforo').exists()).toBe(false);
  });
});
