import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.withCredentials = true;

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

// ─── Connection State ─────────────────────────────────────────────────────────

/**
 * WebSocket connection state tracker.
 *
 * Possible states: 'connecting' | 'connected' | 'disconnected' | 'unavailable' | 'disabled'
 *
 * Validates: Requirements 17.5 (connection status indicator)
 */
window.wsConnectionState = 'disabled';

/**
 * Dispatch a custom DOM event so UI components (Alpine.js, Blade) can react
 * to connection state changes without polling.
 *
 * @param {'connecting'|'connected'|'disconnected'|'unavailable'|'disabled'} state
 */
function setConnectionState(state) {
  window.wsConnectionState = state;
  window.dispatchEvent(new CustomEvent('ws:state-changed', { detail: { state } }));
}

// ─── Exponential Backoff ──────────────────────────────────────────────────────

/**
 * Calculate exponential backoff delay for reconnect attempts.
 *
 * Sequence: 1s, 2s, 4s, 8s, 16s, 30s (capped), 30s, ...
 *
 * @param {number} attempt - zero-based reconnect attempt number
 * @returns {number} delay in milliseconds
 *
 * Validates: Requirements 17.5 (auto-reconnect with exponential backoff)
 */
function exponentialBackoff(attempt) {
  const base = 1000;   // 1 second
  const max  = 30000;  // 30 seconds cap
  return Math.min(base * Math.pow(2, attempt), max);
}

// ─── Laravel Echo (Reverb) ────────────────────────────────────────────────────

const reverbKey = import.meta.env.VITE_REVERB_APP_KEY;
const reverbHost = import.meta.env.VITE_REVERB_HOST;
const reverbEnabled = import.meta.env.VITE_REVERB_ENABLED === 'true'
  || import.meta.env.VITE_BROADCAST_CONNECTION === 'reverb';

if (reverbEnabled && reverbKey && reverbHost) {
  setConnectionState('connecting');

  window.Echo = new Echo({
    broadcaster: 'reverb',
    key:         reverbKey,
    wsHost:      reverbHost,
    wsPort:      import.meta.env.VITE_REVERB_PORT ?? 80,
    wssPort:     import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS:    (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],

    // Pusher.js connection options
    activityTimeout:    30000,
    pongTimeout:        6000,
    unavailableTimeout: 10000,

    auth: {
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
        ...(window.restoAuthToken ? { Authorization: `Bearer ${window.restoAuthToken}` } : {}),
      },
    },
  });

  // ─── Connection Event Hooks ─────────────────────────────────────────────────

  const pusherConnection = window.Echo.connector.pusher.connection;

  /** Track the current reconnect attempt count for backoff calculation. */
  let reconnectAttempt = 0;

  pusherConnection.bind('connecting', () => {
    setConnectionState('connecting');
  });

  pusherConnection.bind('connected', () => {
    setConnectionState('connected');
    // Reset backoff counter on successful connection
    reconnectAttempt = 0;
  });

  pusherConnection.bind('disconnected', () => {
    setConnectionState('disconnected');
  });

  pusherConnection.bind('unavailable', () => {
    setConnectionState('unavailable');
  });

  pusherConnection.bind('failed', () => {
    setConnectionState('disconnected');
  });

  /**
   * Override Pusher's default linear backoff with exponential backoff.
   *
   * Pusher.js fires 'connecting_in' before each reconnect attempt and
   * exposes `connection.reconnectDelay` which we can override.
   *
   * Validates: Requirements 17.5 (1s → 2s → 4s → 8s → 16s → 30s max)
   */
  pusherConnection.bind('connecting_in', () => {
    const backoffDelay = exponentialBackoff(reconnectAttempt);
    reconnectAttempt++;

    // Override the delay Pusher will use for this reconnect attempt
    pusherConnection.reconnectDelay = backoffDelay;
  });
} else {
  window.Echo = null;
  setConnectionState('disabled');
}
