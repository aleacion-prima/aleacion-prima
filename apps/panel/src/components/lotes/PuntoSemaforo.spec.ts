import { mount } from '@vue/test-utils';
import { Quasar } from 'quasar';
import { describe, expect, it } from 'vitest';
import { SEMAFOROS, type Semaforo } from '@aleacion-prima/types';
import PuntoSemaforo from './PuntoSemaforo.vue';

const montar = (semaforo: Semaforo | null, motivos: string[] = [], incompleto?: boolean) =>
  mount(PuntoSemaforo, {
    props: {
      semaforo,
      motivos,
      ...(incompleto !== undefined ? { incompleto } : {}),
    },
    global: { plugins: [Quasar] },
  });

describe('PuntoSemaforo', () => {
  it.each([
    ['verde', 'bg-positive'],
    ['ambar', 'bg-warning'],
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

  it('si faltan datos lo marca aparte y lo dice en el nombre accesible', () => {
    const punto = montar('ambar', [], true).find('.punto-semaforo');

    expect(punto.classes()).toContain('punto-semaforo--incompleto');
    expect(punto.attributes('aria-label')).toContain('Datos incompletos');
  });

  it('con los datos completos no lleva la marca de incompleto', () => {
    const punto = montar('verde', [], false).find('.punto-semaforo');

    expect(punto.classes()).not.toContain('punto-semaforo--incompleto');
    expect(punto.attributes('aria-label')).not.toContain('incompletos');
  });
});
