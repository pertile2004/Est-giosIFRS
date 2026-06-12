/**
 * Service Worker do InternSHIP Conect.
 * Estrategia: cache-first para os assets staticos (CSS, JS, logo),
 * network-first para o resto (paginas PHP).
 */

const CACHE_NAME = 'internship-conect-v1';
const STATIC_ASSETS = [
  '/teste/',
  '/teste/assets/css/style.css',
  '/teste/assets/js/main.js',
  '/teste/assets/img/logo.svg',
  '/teste/assets/img/logo-icon.svg',
  '/teste/manifest.json',
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) =>
      cache.addAll(STATIC_ASSETS).catch(() => {
        // Ignora falhas individuais para nao quebrar o install
      })
    )
  );
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(
        keys
          .filter((k) => k !== CACHE_NAME)
          .map((k) => caches.delete(k))
      )
    )
  );
  self.clients.claim();
});

self.addEventListener('fetch', (event) => {
  const url = new URL(event.request.url);

  // So mexe em GETs do mesmo host
  if (event.request.method !== 'GET' || url.origin !== self.location.origin) {
    return;
  }

  const isStatic = /\.(css|js|svg|png|jpg|jpeg|webp|woff2?)$/i.test(url.pathname);

  if (isStatic) {
    // Cache-first
    event.respondWith(
      caches.match(event.request).then((cached) =>
        cached ||
        fetch(event.request).then((resp) => {
          if (resp.ok) {
            const copy = resp.clone();
            caches.open(CACHE_NAME).then((c) => c.put(event.request, copy));
          }
          return resp;
        }).catch(() => cached)
      )
    );
  } else {
    // Network-first com fallback para cache (paginas)
    event.respondWith(
      fetch(event.request)
        .then((resp) => {
          if (resp.ok) {
            const copy = resp.clone();
            caches.open(CACHE_NAME).then((c) => c.put(event.request, copy));
          }
          return resp;
        })
        .catch(() => caches.match(event.request))
    );
  }
});
