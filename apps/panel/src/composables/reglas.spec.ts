import { describe, expect, it } from 'vitest';
import { coincideCon, contrasenaSegura, correo, requerido } from './reglas';

describe('requerido', () => {
  const regla = requerido('Escribe tu correo');

  it('acepta un valor con texto', () => {
    expect(regla('a')).toBe(true);
  });

  it.each(['', '   '])('rechaza %j con el mensaje dado', (valor) => {
    expect(regla(valor)).toBe('Escribe tu correo');
  });
});

describe('correo', () => {
  it.each(['juan@aleacionprima.test', ' juan.perez@empresa.com.mx '])('acepta %j', (valor) => {
    expect(correo(valor)).toBe(true);
  });

  it.each(['juan', 'juan@', 'juan@empresa', '@empresa.com', 'juan perez@empresa.com'])(
    'rechaza %j',
    (valor) => {
      expect(correo(valor)).toBe('Escribe un correo válido');
    },
  );
});

describe('contrasenaSegura', () => {
  it('acepta una contraseña que cumple la política', () => {
    expect(contrasenaSegura('Aleacion2026')).toBe(true);
  });

  it.each([
    ['Ab1', 'Mínimo 8 caracteres'],
    [`Ab1${'x'.repeat(70)}`, 'Máximo 72 caracteres'],
    ['aleacion2026', 'Incluye mayúsculas y minúsculas'],
    ['ALEACION2026', 'Incluye mayúsculas y minúsculas'],
    ['Aleacionprima', 'Incluye al menos un número'],
  ])('rechaza %j: %s', (valor, mensaje) => {
    expect(contrasenaSegura(valor)).toBe(mensaje);
  });

  it('acepta exactamente 8 y 72 caracteres', () => {
    expect(contrasenaSegura('Aleacio1')).toBe(true);
    expect(contrasenaSegura(`Ab1${'x'.repeat(69)}`)).toBe(true);
  });

  it('cuenta las letras acentuadas', () => {
    expect(contrasenaSegura('ÁRBOLñ2026')).toBe(true);
  });
});

describe('coincideCon', () => {
  it('compara contra el valor actual del otro campo', () => {
    let clave = 'Aleacion2026';
    const regla = coincideCon(() => clave);

    expect(regla('Aleacion2026')).toBe(true);

    clave = 'Otra2026';
    expect(regla('Aleacion2026')).toBe('Las contraseñas no coinciden');
  });
});
