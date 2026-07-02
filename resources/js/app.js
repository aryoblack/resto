import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();

// ─── PWA Service Worker Registration ─────────────────────────────────────────

/**
 * Register the service worker for PWA support and push notifications.
 *
 * The service worker handles:
 *  - Push notification display (Web Push API)
 *  - Offline caching of static assets
 *
 * Validates: Requirements 17.4 (push notification for Customer PWA)
 */
if ('serviceWorker' in navigator) {
  window.addEventListener('load', async () => {
    try {
      const registration = await navigator.serviceWorker.register('/sw.js', {
        scope: '/',
      });

      // Store the registration globally so other modules can use it
      // (e.g., to subscribe to push notifications)
      window.swRegistration = registration;

      console.info('[SW] Service worker registered:', registration.scope);
    } catch (error) {
      console.error('[SW] Service worker registration failed:', error);
    }
  });
}

// ─── Push Notification Subscription Helper ───────────────────────────────────

/**
 * Subscribe the current user to Web Push notifications.
 *
 * Call this after the user has authenticated and granted notification permission.
 *
 * @param {string} vapidPublicKey - The VAPID public key from the server
 * @returns {Promise<PushSubscription|null>}
 */
window.subscribeToPushNotifications = async function (vapidPublicKey) {
  if (!('PushManager' in window)) {
    console.warn('[Push] Push notifications are not supported in this browser.');
    return null;
  }

  const permission = await Notification.requestPermission();
  if (permission !== 'granted') {
    console.warn('[Push] Notification permission denied.');
    return null;
  }

  if (!window.swRegistration) {
    console.warn('[Push] Service worker not registered yet.');
    return null;
  }

  try {
    // Convert the VAPID public key from base64url to Uint8Array
    const applicationServerKey = urlBase64ToUint8Array(vapidPublicKey);

    const subscription = await window.swRegistration.pushManager.subscribe({
      userVisibleOnly: true,
      applicationServerKey,
    });

    // Send the subscription to the server
    await window.axios.post('/api/customer/push-subscribe', {
      endpoint: subscription.endpoint,
      keys: {
        p256dh: arrayBufferToBase64(subscription.getKey('p256dh')),
        auth:   arrayBufferToBase64(subscription.getKey('auth')),
      },
    });

    console.info('[Push] Successfully subscribed to push notifications.');
    return subscription;
  } catch (error) {
    console.error('[Push] Failed to subscribe to push notifications:', error);
    return null;
  }
};

/**
 * Unsubscribe the current user from Web Push notifications.
 *
 * @returns {Promise<boolean>}
 */
window.unsubscribeFromPushNotifications = async function () {
  if (!window.swRegistration) return false;

  try {
    const subscription = await window.swRegistration.pushManager.getSubscription();
    if (!subscription) return true;

    // Notify the server to remove the subscription
    await window.axios.delete('/api/customer/push-subscribe', {
      data: { endpoint: subscription.endpoint },
    });

    await subscription.unsubscribe();
    console.info('[Push] Unsubscribed from push notifications.');
    return true;
  } catch (error) {
    console.error('[Push] Failed to unsubscribe:', error);
    return false;
  }
};

// ─── Utility Functions ────────────────────────────────────────────────────────

/**
 * Convert a base64url string to a Uint8Array (required for VAPID key).
 *
 * @param {string} base64String
 * @returns {Uint8Array}
 */
function urlBase64ToUint8Array(base64String) {
  const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
  const base64  = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
  const rawData = window.atob(base64);
  return Uint8Array.from([...rawData].map((char) => char.charCodeAt(0)));
}

/**
 * Convert an ArrayBuffer to a base64 string.
 *
 * @param {ArrayBuffer|null} buffer
 * @returns {string}
 */
function arrayBufferToBase64(buffer) {
  if (!buffer) return '';
  return btoa(String.fromCharCode(...new Uint8Array(buffer)));
}
