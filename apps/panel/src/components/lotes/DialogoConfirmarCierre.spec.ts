import { mount } from '@vue/test-utils';
import { Quasar } from 'quasar';
import { describe, expect, it, vi } from 'vitest';
import type { Lote, LoteParada } from '@aleacion-prima/types';
import DialogoConfirmarCierre from './DialogoConfirmarCierre.vue';

vi.mock('@/services/api', () => ({ useApi: vi.fn() }));

const parada = (orden: number, cambios: Partial<LoteParada>): LoteParada => ({
  id: orden,
  orden,
  area: { id: orden, codigo: `A${orden}`, nombre: `Área ${orden}` },
  piezas_recibidas: null,
  piezas_entregadas: null,
  merma_piezas: null,
  merma_material: null,
  retorno_kg: null,
  discrepancia: null,
  entrada_ocurrido_en: null,
  entrada_registrado_en: null,
  entrada_tardia: false,
  salida_ocurrido_en: null,
  salida_registrado_en: null,
  salida_tardia: false,
  admite_retorno: false,
  puede_capturar: true,
  ...cambios,
});

const lote = (cantidad: number, paradas: LoteParada[]): Lote => ({
  id: 1,
  codigo: 'LOT-20261120-01',
  estado: 'por_confirmar',
  cantidad_objetivo: cantidad,
  piezas_terminadas: null,
  producto: { id: 1, codigo: 'POL-IND-G', nombre: 'Polea industrial' },
  area_actual: null,
  paradas,
  confirmado_en: null,
  cancelado_en: null,
  motivo_cancelacion: null,
  admite_devolucion: false,
  created_at: '2026-11-20T09:00:00Z',
});

const resumen = (datos: Lote): string =>
  mount(DialogoConfirmarCierre, {
    props: { modelValue: true, lote: datos },
    global: { plugins: [Quasar], stubs: { QDialog: { template: '<div><slot /></div>' } } },
  })
    .findAll('.text-overline')
    .map((dato) => `${dato.text()} ${dato.element.nextElementSibling?.textContent?.trim() ?? ''}`)
    .join(' · ');

describe('DialogoConfirmarCierre', () => {
  it('separa la merma registrada de las piezas que faltaron entre áreas', () => {
    const texto = resumen(
      lote(12, [
        parada(1, { piezas_recibidas: 12, piezas_entregadas: 12, merma_piezas: 0 }),
        parada(2, { piezas_recibidas: 12, piezas_entregadas: 11, merma_piezas: 1 }),
        parada(3, {
          piezas_recibidas: 10,
          piezas_entregadas: 10,
          merma_piezas: 0,
          discrepancia: -1,
        }),
      ]),
    );

    expect(texto).toContain('Terminadas 10');
    expect(texto).toContain('Merma 1');
    expect(texto).toContain('Faltantes 1');
    expect(texto).toContain('Rendimiento 83 %');
  });

  it('no muestra faltantes cuando toda la diferencia es merma', () => {
    const texto = resumen(
      lote(50, [
        parada(1, { piezas_recibidas: 50, piezas_entregadas: 48, merma_piezas: 2 }),
        parada(2, { piezas_recibidas: 48, piezas_entregadas: 46, merma_piezas: 2 }),
      ]),
    );

    expect(texto).toContain('Terminadas 46');
    expect(texto).toContain('Merma 4');
    expect(texto).not.toContain('Faltantes');
  });
});
