import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import {
  PUBLIC_REVERB_HOST,
  PUBLIC_REVERB_KEY,
  PUBLIC_REVERB_PORT,
  PUBLIC_REVERB_SCHEME,
} from 'astro:env/client';

declare global {
  interface Window {
    Pusher: typeof Pusher;
  }
}

// Este modulo se importa tambien durante el prerender estatico de Astro,
// donde "window" no existe; el asigno solo aplica en el navegador.
if (typeof window !== 'undefined') {
  window.Pusher = Pusher;
}

let instancia: Echo<'reverb'> | undefined;

// Sitio publico y sin sesion: no se define "authorizer" porque, por ahora,
// solo se van a usar canales publicos (sin necesidad de /broadcasting/auth).
export const obtenerEcho = (): Echo<'reverb'> => {
  instancia ??= new Echo({
    broadcaster: 'reverb',
    key: PUBLIC_REVERB_KEY,
    wsHost: PUBLIC_REVERB_HOST,
    wsPort: PUBLIC_REVERB_PORT,
    wssPort: PUBLIC_REVERB_PORT,
    forceTLS: PUBLIC_REVERB_SCHEME === 'https',
    enabledTransports: ['ws', 'wss'],
  });
  return instancia;
};

export const desconectarEcho = (): void => {
  instancia?.disconnect();
  instancia = undefined;
};
