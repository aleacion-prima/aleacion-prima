export type Regla = (valor: string) => true | string;

const FORMATO_CORREO = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

export const requerido =
  (mensaje: string): Regla =>
  (valor) =>
    valor.trim() !== '' || mensaje;

export const correo: Regla = (valor) =>
  FORMATO_CORREO.test(valor.trim()) || 'Escribe un correo válido';

export const contrasenaSegura: Regla = (valor) => {
  if (valor.length < 8) return 'Mínimo 8 caracteres';
  if (valor.length > 72) return 'Máximo 72 caracteres';
  if (!/\p{Lu}/u.test(valor) || !/\p{Ll}/u.test(valor)) return 'Incluye mayúsculas y minúsculas';
  if (!/\p{N}/u.test(valor)) return 'Incluye al menos un número';
  return true;
};

export const coincideCon =
  (otro: () => string): Regla =>
  (valor) =>
    valor === otro() || 'Las contraseñas no coinciden';
