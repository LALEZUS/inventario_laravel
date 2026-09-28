const CACHE_NAME = 'inventario-ti-static-v1';
const STATIC_ASSETS = [
    './manifest.webmanifest',
    './favicon.ico',
    './images/logo-white.png',
    './css/app.css',
];

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(CACHE_NAME).then((cache) => cache.addAll(STATIC_ASSETS)));
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(caches.keys().then((keys) => Promise.all(
        keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key))
    )));
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    if (event.request.method !== 'GET' || new URL(event.request.url).origin !== self.location.origin) return;
    const request = event.request;
    if (request.mode === 'navigate') return;
    event.respondWith(caches.match(request).then((cached) => cached || fetch(request).then((response) => {
        if (response.ok && ['style', 'script', 'image', 'font'].includes(request.destination)) {
            const copy = response.clone();
            caches.open(CACHE_NAME).then((cache) => cache.put(request, copy));
        }
        return response;
    })));
});
