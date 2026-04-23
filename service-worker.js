const CACHE_NAME = 'site-professor-pwa-v5';

self.addEventListener('install', (event) => {
    event.waitUntil((async () => {
        const cache = await caches.open(CACHE_NAME);
        const scopeUrl = new URL(self.registration.scope);
        const assets = [
            new URL('./', scopeUrl).toString(),
            new URL('./index.php?page=home', scopeUrl).toString(),
            new URL('./index.php?page=manifest', scopeUrl).toString(),
            new URL('./index.php?page=app-icon&size=192', scopeUrl).toString(),
            new URL('./assets/styles.css', scopeUrl).toString(),
        ];

        await cache.addAll(assets);
        self.skipWaiting();
    })());
});

self.addEventListener('activate', (event) => {
    event.waitUntil((async () => {
        const keys = await caches.keys();
        await Promise.all(keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key)));
        await self.clients.claim();
    })());
});

self.addEventListener('fetch', (event) => {
    if (event.request.method !== 'GET') {
        return;
    }

    const requestUrl = new URL(event.request.url);

    if (requestUrl.origin !== self.location.origin) {
        return;
    }

    const isDynamicPage = event.request.mode === 'navigate'
        || requestUrl.pathname.endsWith('/index.php')
        || requestUrl.pathname.endsWith('/');

    event.respondWith((async () => {
        const cache = await caches.open(CACHE_NAME);

        if (isDynamicPage) {
            try {
                const response = await fetch(event.request, { cache: 'no-store' });

                if (response.ok) {
                    cache.put(event.request, response.clone());
                }

                return response;
            } catch (error) {
                const cached = await caches.match(event.request);

                if (cached) {
                    return cached;
                }

                const fallback = await caches.match(new URL('./index.php?page=home', self.registration.scope).toString());

                if (fallback) {
                    return fallback;
                }

                throw error;
            }
        }

        const cached = await caches.match(event.request);

        if (cached) {
            return cached;
        }

        const response = await fetch(event.request);

        if (response.ok) {
            cache.put(event.request, response.clone());
        }

        return response;
    })());
});
