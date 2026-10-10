import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import {
  PUBLIC_REVERB_HOST,
  PUBLIC_REVERB_KEY,
  PUBLIC_REVERB_PORT,
  PUBLIC_REVERB_SCHEME,
} from 'astro:env/client';

let echo: Echo<'reverb'> | null = null;

export function obtenerEcho(): Echo<'reverb'> {
  if (echo) return echo;

  const usaTls = PUBLIC_REVERB_SCHEME === 'https';
  const puerto = Number(PUBLIC_REVERB_PORT);

  echo = new Echo({
    broadcaster: 'reverb',
    key: PUBLIC_REVERB_KEY,
    wsHost: PUBLIC_REVERB_HOST,
    wsPort: puerto,
    wssPort: puerto,
    forceTLS: usaTls,
    enabledTransports: usaTls ? ['wss'] : ['ws'],
    Pusher,
  });

  return echo;
}
