const FORMATO = /^LOT-\d{8}-\d{2,}$/;

export const normalizarCodigo = (texto: string): string => texto.trim().toUpperCase();

export const esCodigoLote = (texto: string): boolean => FORMATO.test(normalizarCodigo(texto));
