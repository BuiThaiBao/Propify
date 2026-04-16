import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

// Bật Pusher console log trong dev để debug WebSocket
if (import.meta.env.DEV) {
  Pusher.logToConsole = true;
}

/** Singleton instance — được khởi tạo sau khi user login */
let echoInstance = null;

export function getEcho() {
  return echoInstance;
}

export function createEcho(token) {
  const key     = import.meta.env.VITE_REVERB_APP_KEY;
  const host    = import.meta.env.VITE_REVERB_HOST    ?? 'localhost';
  const port    = parseInt(import.meta.env.VITE_REVERB_PORT ?? '8080');
  const scheme  = import.meta.env.VITE_REVERB_SCHEME  ?? 'http';
  const apiUrl  = import.meta.env.VITE_API_URL ?? 'http://localhost:8000/api';

  console.log('[Echo] Init with key:', key, '| host:', host, '| port:', port);

  const echo = new Echo({
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

  // Log connection state changes
  echo.connector.pusher.connection.bind('state_change', ({ current }) => {
    console.log(`[Echo] Connection state → ${current}`);
  });

  echo.connector.pusher.connection.bind('error', (err) => {
    console.error('[Echo] Connection error:', err);
  });

  echo.connector.pusher.connection.bind('connected', () => {
    console.log('[Echo] ✅ Connected to Reverb WebSocket!');
  });

  echo.connector.pusher.connection.bind('disconnected', () => {
    console.warn('[Echo] ❌ Disconnected from Reverb');
  });

  return echo;
}

export function initEcho(token) {
  if (echoInstance) {
    echoInstance.disconnect();
  }
  echoInstance = createEcho(token);
  return echoInstance;
}

export function destroyEcho() {
  if (echoInstance) {
    echoInstance.disconnect();
    echoInstance = null;
  }
}

