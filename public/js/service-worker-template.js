const BASE_PATH = '__BASE_PATH__';
const CACHE_VERSION = '__CACHE_VERSION__';
const STATIC_CACHE = `teamspace-static-${CACHE_VERSION}`;
const OFFLINE_URL = `${BASE_PATH}/offline`;
const OFFLINE_TEXT = 'オフラインのため表示できません。接続を確認して再試行してください。';

const STATIC_ASSETS = [
  `${BASE_PATH}/css/style.css`,
  `${BASE_PATH}/css/home.css`,
  `${BASE_PATH}/css/task.css`,
  `${BASE_PATH}/js/app.js`,
  `${BASE_PATH}/img_icon/favicon.svg`,
  OFFLINE_URL
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(STATIC_CACHE).then((cache) => cache.addAll(STATIC_ASSETS)).then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys()
      .then((keys) =>
        Promise.all(
          keys
            .filter((key) => key.startsWith('teamspace-static-') && key !== STATIC_CACHE)
            .map((key) => caches.delete(key))
        )
      )
      .then(() => self.clients.claim())
  );
});

const shouldBypassCache = (requestUrl) => {
  if (requestUrl.origin !== self.location.origin) {
    return true;
  }
  const path = requestUrl.pathname;
  if (path.startsWith(`${BASE_PATH}/api/`)) {
    return true;
  }
  if (path === `${BASE_PATH}/login` || path === `${BASE_PATH}/logout`) {
    return true;
  }
  return false;
};

const fallbackTextResponse = (statusCode = 503) =>
  new Response(OFFLINE_TEXT, {
    status: statusCode,
    headers: { 'Content-Type': 'text/plain; charset=utf-8' }
  });

self.addEventListener('fetch', (event) => {
  if (event.request.method !== 'GET') {
    return;
  }

  const requestUrl = new URL(event.request.url);
  if (shouldBypassCache(requestUrl)) {
    return;
  }

  if (event.request.mode === 'navigate') {
    event.respondWith(
      fetch(event.request)
        .then((response) => response)
        .catch(() =>
          caches.match(event.request).then((cached) => {
            if (cached) {
              return cached;
            }
            return caches.match(OFFLINE_URL).then((offlinePage) => offlinePage || fallbackTextResponse(503));
          })
        )
    );
    return;
  }

  event.respondWith(
    caches.match(event.request).then((cached) => {
      const networkFetch = fetch(event.request)
        .then((response) => {
          const cacheableDestination = ['style', 'script', 'image', 'font'].includes(event.request.destination);
          const cacheControl = response.headers.get('Cache-Control') || '';
          const isNoStore = /no-store/i.test(cacheControl);
          if (response && response.status === 200 && response.type === 'basic' && cacheableDestination && !isNoStore) {
            const clone = response.clone();
            caches.open(STATIC_CACHE).then((cache) => cache.put(event.request, clone));
          }
          return response;
        })
        .catch(() => null);

      if (cached) {
        return networkFetch.then((networkResponse) => networkResponse || cached);
      }

      return networkFetch.then((networkResponse) => networkResponse || fallbackTextResponse(504));
    })
  );
});

self.addEventListener('push', (event) => {
  let payload = {};
  try {
    payload = event.data ? event.data.json() : {};
  } catch (error) {
    payload = {};
  }

  const title = payload.title || 'TeamSpace 通知';
  const options = {
    body: payload.body || '新しい通知があります。',
    icon: `${BASE_PATH}/public/icons/pwa-192.png`,
    badge: `${BASE_PATH}/public/icons/pwa-192.png`,
    data: {
      url: payload.url || `${BASE_PATH}/notifications`,
      tag: payload.tag || 'teamspace-notification'
    },
    tag: payload.tag || 'teamspace-notification',
    renotify: false
  };

  event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  const targetUrl = event.notification.data?.url || `${BASE_PATH}/`;

  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windows) => {
      for (const client of windows) {
        if (client.url.includes(BASE_PATH) && 'focus' in client) {
          client.navigate(targetUrl);
          return client.focus();
        }
      }
      if (clients.openWindow) {
        return clients.openWindow(targetUrl);
      }
      return null;
    })
  );
});

self.addEventListener('message', (event) => {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
});
