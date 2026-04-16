import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

/**
 * Tạo và export Echo instance được cấu hình cho Laravel Reverb.
 *
 * Reverb tương thích giao thức Pusher, nên dùng broadcaster: 'reverb'.
 * Token được đọc từ localStorage mỗi khi Echo được khởi tạo.
 *
 * authEndpoint trỏ về /api/broadcasting/auth — được handle bởi
 * Laravel Sanctum/JWT qua JwtBroadcastingMiddleware.
 */
export function createEcho(token) {
  return new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST ?? 'localhost',
    wsPort: parseInt(import.meta.env.VITE_REVERB_PORT ?? '8080'),
    wssPort: parseInt(import.meta.env.VITE_REVERB_PORT ?? '8080'),
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'http') === 'https',
    enabledTransports: ['ws', 'wss'],
    authEndpoint: `${import.meta.env.VITE_API_URL}/broadcasting/auth`,
    auth: {
      headers: {
        Authorization: `Bearer ${token}`,
        Accept: 'application/json',
      },
    },
  });
}

/** Singleton instance — được khởi tạo sau khi user login */
let echoInstance = null;

export function getEcho() {
  return echoInstance;
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
