import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import type { ChannelAuthorizationCallback } from 'pusher-js';
import { API_URL, cabecerasApi } from './api';

declare global {
  interface Window {
    Pusher: typeof Pusher;
  }
}

window.Pusher = Pusher;

let instancia: Echo<'reverb'> | undefined;

export const obtenerEcho = (): Echo<'reverb'> => {
  instancia ??= new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.PUBLIC_REVERB_KEY,
    wsHost: import.meta.env.PUBLIC_REVERB_HOST,
    wsPort: Number(import.meta.env.PUBLIC_REVERB_PORT),
    wssPort: Number(import.meta.env.PUBLIC_REVERB_PORT),
    forceTLS: import.meta.env.PUBLIC_REVERB_SCHEME === 'https',
    enabledTransports: ['ws', 'wss'],
    authorizer: (channel: { name: string }) => ({
      authorize: (socketId: string, callback: ChannelAuthorizationCallback) => {
        void fetch(`${API_URL}/broadcasting/auth`, {
          method: 'POST',
          credentials: 'include',
          headers: cabecerasApi({ 'Content-Type': 'application/json' }),
          body: JSON.stringify({ socket_id: socketId, channel_name: channel.name }),
        })
          .then(async (respuesta) => {
            if (!respuesta.ok) throw new Error(`Canal rechazado (${respuesta.status})`);
            callback(null, (await respuesta.json()) as Parameters<ChannelAuthorizationCallback>[1]);
          })
          .catch((e: unknown) => {
            callback(e instanceof Error ? e : new Error(String(e)), null);
          });
      },
    }),
  });
  return instancia;
};

export const desconectarEcho = (): void => {
  instancia?.disconnect();
  instancia = undefined;
};
