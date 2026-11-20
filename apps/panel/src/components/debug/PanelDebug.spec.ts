import { flushPromises, mount } from '@vue/test-utils';
import { Quasar } from 'quasar';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import type { Usuario } from '@aleacion-prima/types';
import PanelDebug from './PanelDebug.vue';

const usuarios: Usuario[] = [
  {
    id: 1,
    name: 'Administrador de prueba',
    email: 'admin@aleacionprima.test',
    rol: 'administrador',
    activo: true,
    dado_de_baja_en: null,
    areas: [],
  },
  {
    id: 3,
    name: 'Encargado de prueba',
    email: 'encargado@aleacionprima.test',
    rol: 'encargado',
    activo: true,
    dado_de_baja_en: null,
    areas: [{ id: 1, codigo: 'MOL', nombre: 'Moldeo' }],
  },
];

const listarUsuarios = vi.fn(() => Promise.resolve(usuarios));
const avanzarLote = vi.fn<(codigo: string, avance: object) => Promise<object>>(() =>
  Promise.resolve({}),
);
const crearLote = vi.fn(() => Promise.resolve({ codigo: 'LOT-20261117-02' }));
const crearVariosLotes = vi.fn<(lotes: number) => Promise<object[]>>(() => Promise.resolve([]));
const push = vi.fn();

vi.mock('@/composables/modoDebug', () => ({
  listarUsuarios: () => listarUsuarios(),
  avanzarLote: (codigo: string, avance: object) => avanzarLote(codigo, avance),
  crearLote: () => crearLote(),
  crearVariosLotes: (lotes: number) => crearVariosLotes(lotes),
  alternarAreas: vi.fn(),
  rellenarInventario: vi.fn(),
  entrarComo: vi.fn(),
  reiniciarBd: vi.fn(),
}));
vi.mock('vue-router', () => ({ useRouter: () => ({ push }) }));

const montar = async (codigo: string | null, usuarioActual = 1) => {
  const wrapper = mount(PanelDebug, {
    props: { codigo, usuarioActual },
    global: { plugins: [Quasar] },
  });
  await flushPromises();
  return wrapper;
};

const boton = (wrapper: Awaited<ReturnType<typeof montar>>, texto: string) => {
  const encontrado = wrapper.findAll('button').find((b) => b.text().includes(texto));
  if (!encontrado) throw new Error(`No hay botón "${texto}"`);
  return encontrado;
};

describe('PanelDebug', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('lista los usuarios con su rol y sus áreas', async () => {
    const texto = (await montar(null)).text();

    expect(texto).toContain('Administrador de prueba');
    expect(texto).toContain('encargado · MOL');
  });

  it('solo muestra los trucos del lote en su detalle', async () => {
    expect((await montar(null)).text()).not.toContain('Este lote');
    expect((await montar('LOT-20261117-01')).text()).toContain('Este lote');
  });

  it('avanza el lote hasta el final y avisa para recargar la pantalla', async () => {
    const wrapper = await montar('LOT-20261117-01');

    await boton(wrapper, 'Al final').trigger('click');
    await flushPromises();

    expect(avanzarLote).toHaveBeenCalledWith('LOT-20261117-01', { hasta: 'final' });
    expect(wrapper.emitted('actualizado')).toHaveLength(1);
  });

  it('crea un lote de prueba y abre su detalle', async () => {
    const wrapper = await montar(null);

    await boton(wrapper, 'Crear lote de prueba').trigger('click');
    await flushPromises();

    expect(push).toHaveBeenCalledWith('/lotes/LOT-20261117-02');
  });

  it('crea varios lotes y abre la lista', async () => {
    const wrapper = await montar(null);

    await boton(wrapper, 'Crear 5 lotes').trigger('click');
    await flushPromises();

    expect(crearVariosLotes).toHaveBeenCalledWith(5);
    expect(push).toHaveBeenCalledWith('/lotes');
    expect(wrapper.emitted('actualizado')).toHaveLength(1);
  });

  it('solo ofrece alternar áreas al encargado', async () => {
    expect((await montar(null, 1)).text()).not.toContain('Alternar todas mis áreas');
    expect((await montar(null, 3)).text()).toContain('Alternar todas mis áreas');
  });
});
