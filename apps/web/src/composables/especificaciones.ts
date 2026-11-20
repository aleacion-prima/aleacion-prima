import type { ProductoPublico } from '@aleacion-prima/types';

export interface DatoTecnico {
  etiqueta: string;
  valor: string;
  mono: boolean;
}

const texto = (valor: string | number | undefined): string =>
  valor === undefined ? '' : String(valor);

export const material = (producto: ProductoPublico): string => {
  const base = texto(producto.especificaciones?.material);
  const norma = texto(producto.especificaciones?.norma);
  return norma ? `${base} (${norma})` : base;
};

export const datosTecnicos = (producto: ProductoPublico): DatoTecnico[] => {
  const especificaciones = producto.especificaciones ?? {};
  const datos: DatoTecnico[] = [];
  if (material(producto))
    datos.push({ etiqueta: 'Material', valor: material(producto), mono: false });
  if (especificaciones.peso_kg !== undefined) {
    datos.push({
      etiqueta: 'Peso en bruto',
      valor: `${texto(especificaciones.peso_kg)} kg`,
      mono: true,
    });
  }
  if (especificaciones.diametro_mm !== undefined) {
    datos.push({
      etiqueta: 'Diámetro',
      valor: `${texto(especificaciones.diametro_mm)} mm`,
      mono: true,
    });
  }
  return datos;
};
