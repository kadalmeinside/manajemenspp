const CACHE_NAME = 'spp-pjh-cache-v1';
const urlsToCache = [
    '/',
    '/login',
    '/manifest.json',
    '/icons/icon-192.png',
    '/icons/icon-512.png',
];

// Instal SW dan cache file statis
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                console.log('Opened cache');
                return cache.addAll(urlsToCache);
            })
    );
});

// Bersihkan cache lama
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.filter(cacheName => {
                    return cacheName !== CACHE_NAME;
                }).map(cacheName => {
                    return caches.delete(cacheName);
                })
            );
        })
    );
});

// Fetch (Network First, fallback to cache)
self.addEventListener('fetch', event => {
    // Abaikan request non-GET dan request ke API eksternal
    if (event.request.method !== 'GET' || !event.request.url.startsWith(self.location.origin)) {
        return;
    }

    event.respondWith(
        fetch(event.request)
            .then(response => {
                // Jangan cache response yang tidak valid (misal 404, 500)
                if (!response || response.status !== 200 || response.type !== 'basic') {
                    return response;
                }

                // Clone response karena response stream hanya bisa dibaca sekali
                const responseToCache = response.clone();

                caches.open(CACHE_NAME)
                    .then(cache => {
                        cache.put(event.request, responseToCache);
                    });

                return response;
            })
            .catch(() => {
                // Jika offline, coba ambil dari cache
                return caches.match(event.request);
            })
    );
});
