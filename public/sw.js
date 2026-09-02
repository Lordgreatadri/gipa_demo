const CACHE_NAME = 'iomp-public-v2';
const OFFLINE_URL = '/offline.html';
const PRECACHE_URLS = ['/', OFFLINE_URL, '/manifest.webmanifest', '/icons/iomp-icon.svg'];
const PRIVATE_PATHS = ['/admin', '/api', '/c/', '/login', '/logout', '/dashboard', '/portal', '/staff'];

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(CACHE_NAME).then((cache) => cache.addAll(PRECACHE_URLS)));
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key))))
            .then(() => self.clients.claim()),
    );
});

self.addEventListener('fetch', (event) => {
    const request = event.request;
    const url = new URL(request.url);

    if (request.method !== 'GET' || url.origin !== self.location.origin || PRIVATE_PATHS.some((path) => url.pathname.startsWith(path))) {
        return;
    }

    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request)
                .then((response) => {
                    if (response.ok && !response.headers.get('Cache-Control')?.includes('private')) {
                        const copy = response.clone();
                        caches.open(CACHE_NAME).then((cache) => cache.put(request, copy));
                    }
                    return response;
                })
                .catch(async () => (await caches.match(request)) ?? caches.match(OFFLINE_URL)),
        );
        return;
    }

    if (['style', 'script', 'image', 'font'].includes(request.destination)) {
        event.respondWith(
            caches.match(request).then((cached) => cached ?? fetch(request).then((response) => {
                if (response.ok) {
                    const copy = response.clone();
                    caches.open(CACHE_NAME).then((cache) => cache.put(request, copy));
                }
                return response;
            })),
        );
    }
});