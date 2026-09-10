const CACHE = 'rentflow-static-9121d6dc58a42291';
const STATIC_PATHS = ["/build/assets/app-V5JNn5EM.css","/build/assets/app-r6jEpxky.js","/icons/icon-192.png","/icons/icon-512.png","/manifest.json","/offline.html"];
const ALLOWED = new Set(STATIC_PATHS);

async function publicAsset(path) {
    const response = await fetch(path, { credentials: 'omit', cache: 'no-store', redirect: 'error' });
    const type = response.headers.get('content-type') || '';
    const expected = path.endsWith('.html') ? 'text/html' : path.endsWith('.css') ? 'text/css'
        : path.endsWith('.js') ? 'javascript' : path.endsWith('.json') ? 'json'
        : path.endsWith('.png') ? 'image/png' : path.endsWith('.svg') ? 'image/svg' : 'font';
    if (!response.ok || response.redirected || !type.includes(expected)) throw new Error('Invalid public asset');
    return response;
}

self.addEventListener('install', event => {
    event.waitUntil((async () => {
        const cache = await caches.open(CACHE);
        await Promise.all(STATIC_PATHS.map(async path => cache.put(path, await publicAsset(path))));
        await self.skipWaiting();
    })());
});

self.addEventListener('activate', event => {
    event.waitUntil((async () => {
        const keys = await caches.keys();
        await Promise.all(keys.filter(key => key.startsWith('rentflow-static-') && key !== CACHE).map(key => caches.delete(key)));
        await self.clients.claim();
    })());
});

self.addEventListener('fetch', event => {
    const request = event.request;
    const url = new URL(request.url);
    if (request.method !== 'GET' || url.origin !== self.location.origin) return;
    if (!url.search && ALLOWED.has(url.pathname)) {
        event.respondWith((async () => {
            const cache = await caches.open(CACHE);
            const stored = await cache.match(url.pathname);
            if (stored) return stored;
            const response = await publicAsset(url.pathname);
            await cache.put(url.pathname, response.clone());
            return response;
        })());
        return;
    }
    if (request.mode === 'navigate') {
        event.respondWith(fetch(request, { cache: 'no-store' }).catch(async () => {
            const cache = await caches.open(CACHE);
            return await cache.match('/offline.html') || new Response('', { status: 503 });
        }));
    }
});
