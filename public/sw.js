const CACHE_NAME = 'mister-takoyaki-static-v3';

self.addEventListener('install', () => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((cacheNames) => Promise.all(
                cacheNames
                    .filter((cacheName) => cacheName !== CACHE_NAME)
                    .map((cacheName) => caches.delete(cacheName))
            ))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const request = event.request;
    const url = new URL(request.url);
    const isStaticAsset = ['script', 'style', 'image', 'font'].includes(request.destination);

    // Bypass non-GET, external origins, non-static assets, and Vite hashed build bundles (/build/)
    // Letting Vite build assets load natively prevents cross-world service worker preload mismatches
    if (request.method !== 'GET' || url.origin !== self.location.origin || !isStaticAsset || url.pathname.startsWith('/build/')) {
        return;
    }

    event.respondWith(
        caches.match(request).then((cachedResponse) => {
            const networkResponse = fetch(request).then((response) => {
                if (response.ok) {
                    const responseCopy = response.clone();
                    caches.open(CACHE_NAME).then((cache) => cache.put(request, responseCopy));
                }
                return response;
            });

            return cachedResponse || networkResponse;
        })
    );
});