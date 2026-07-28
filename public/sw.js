// Minimal service worker: just enough for "installable" PWA status.
// The app page itself and all API calls (/log, /feedback, /admin) always go
// straight to the network — they carry session/CSRF state that must never
// be served stale from a cache. Only the static icons/manifest are cached.
const CACHE = 'pad-preview-v1';
const STATIC_ASSETS = [
  '/manifest.webmanifest',
  '/img/icon-192.png',
  '/img/icon-512.png',
  '/img/pony-express.png',
];

self.addEventListener('install', event => {
  event.waitUntil(caches.open(CACHE).then(cache => cache.addAll(STATIC_ASSETS)));
  self.skipWaiting();
});

self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(keys => Promise.all(keys.filter(k => k !== CACHE).map(k => caches.delete(k))))
  );
  self.clients.claim();
});

self.addEventListener('fetch', event => {
  const url = new URL(event.request.url);
  if(event.request.method === 'GET' && STATIC_ASSETS.includes(url.pathname)){
    event.respondWith(caches.match(event.request).then(cached => cached || fetch(event.request)));
    return;
  }
  event.respondWith(fetch(event.request));
});
