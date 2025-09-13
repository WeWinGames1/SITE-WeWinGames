// Add message event listener immediately to prevent timing warnings
self.addEventListener('message', (event) => {
    // Handle messages if needed
});

// Import OneSignal Service Worker
try {
    importScripts("https://cdn.onesignal.com/sdks/web/v16/OneSignalSDK.sw.js");
} catch (e) {
    console.warn('OneSignal SDK import warning (safe to ignore):', e.message);
}

// Your existing cache configuration
const CACHE_NAME = 'wwg-cache-v1.7.0';
const urlsToCache = [
  '/images/hero.jpg',
  '/images/profit-picks.png',
  '/favicon.ico',
];

// Only add our custom event listeners if they don't conflict with OneSignal
if (!self.WWG_INITIALIZED) {
    self.WWG_INITIALIZED = true;
    
    self.addEventListener('install', event => {
        event.waitUntil(
            caches.open(CACHE_NAME).then(cache => cache.addAll(urlsToCache))
        );
        // Force activation
        self.skipWaiting();
    });

    self.addEventListener('activate', event => {
        event.waitUntil(
            caches.keys().then(cacheNames => {
                return Promise.all(
                    cacheNames.map(cacheName => {
                        if (cacheName !== CACHE_NAME && !cacheName.includes('OneSignal')) {
                            return caches.delete(cacheName);
                        }
                    })
                );
            })
        );
        // Take control of all pages immediately
        self.clients.claim();
    });

    self.addEventListener('fetch', event => {
        // Skip the cache for requests to the API, admin routes, OneSignal, and POST requests
        if (event.request.url.includes('/api/') || 
            event.request.url.includes('/admin/') ||
            event.request.method !== 'GET' ||
            event.request.url.includes('onesignal.com') ||
            event.request.url.includes('OneSignalSDK')) {
            return;
        }
        
        event.respondWith(
            caches.match(event.request).then(response => response || fetch(event.request))
        );
    });
}