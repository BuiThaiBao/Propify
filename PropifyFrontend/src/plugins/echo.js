import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

/** Singleton instance — được khởi tạo sau khi user login */
let echoInstance = null;

export function getEcho() {
  return echoInstance;
}

export function createEcho(token) {
  const key    = import.meta.env.VITE_REVERB_APP_KEY;
  const host   = import.meta.env.VITE_REVERB_HOST   ?? 'localhost';
  const port   = parseInt(import.meta.env.VITE_REVERB_PORT ?? '8080');
  const scheme = import.meta.env.VITE_REVERB_SCHEME ?? 'http';
  const apiUrl = import.meta.env.VITE_API_URL ?? 'http://localhost:8000/api';

  return new Echo({
    broadcaster      : 'reverb',
    key,
    wsHost           : host,
    wsPort           : port,
    wssPort          : port,
    forceTLS         : scheme === 'https',
    enabledTransports: ['ws', 'wss'],
    authEndpoint     : `${apiUrl}/broadcasting/auth`,
    auth: {
      headers: {
        Authorization: `Bearer ${token}`,
        Accept       : 'application/json',
      },
    },
  });
}

export function initEcho(token) {
  if (echoInstance) return echoInstance; // Đã có connection → giữ nguyên
  echoInstance = createEcho(token);

  const pusher = echoInstance.connector?.pusher;
  if (pusher?.connection) {
    pusher.connection.bind('unavailable', () => {
      console.warn('[Echo] Connection unavailable');
    });
    pusher.connection.bind('connected', () => {
      console.log('[Echo] Connected/Reconnected');
    });
    pusher.connection.bind('failed', () => {
      console.error('[Echo] Connection failed permanently');
    });
  }

  return echoInstance;
}

export function destroyEcho() {
  if (echoInstance) {
    echoInstance.disconnect();
    echoInstance = null;
  }
}

