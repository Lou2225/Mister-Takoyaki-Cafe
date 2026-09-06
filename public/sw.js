const CACHE_NAME = 'mister-takoyaki-static-v1';

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

    if (request.method !== 'GET' || url.origin !== self.location.origin || !isStaticAsset) {
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