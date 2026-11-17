import { Preferences } from '@capacitor/preferences';

const CLAVE = 'token';
let token: string | undefined | null;

export const leerToken = async (): Promise<string | null> => {
  if (token === undefined) token = (await Preferences.get({ key: CLAVE })).value;

  return token;
};

export const guardarToken = async (nuevo: string): Promise<void> => {
  token = nuevo;
  await Preferences.set({ key: CLAVE, value: nuevo });
};

export const borrarToken = async (): Promise<void> => {
  token = null;
  await Preferences.remove({ key: CLAVE });
};
