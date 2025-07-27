const CACHE_NAME = 'wwg-cache-v1.7.0';
const urlsToCache = [
  '/images/hero.jpg',
  '/images/profit-picks.png',
  '/favicon.ico',
  //'/css/app.css',
  //'/js/app.js',
  // Add more assets as needed
];

self.addEventListener('install', event => {
    event.waitUntil(
    caches.open(CACHE_NAME).then(cache => cache.addAll(urlsToCache))
  );
});

self.addEventListener('fetch', event => {
    // Skip the cache for requests to the API, admin routes, and POST requests
  if (event.request.url.includes('/api/') || 
      event.request.url.includes('/admin/') ||
      event.request.method !== 'GET') {
    return;
  }
  event.respondWith(
    caches.match(event.request).then(response => response || fetch(event.request))
  );
});

self.addEventListener('push', event => {
    const data = event.data.json();
    const options = {
        body: data.body,
        icon: data.icon || '/images/icons/icon-192x192.png',
        badge: '/images/icons/icon-96x96.png',
        vibrate: [100, 50, 100],
        data: {
            dateOfArrival: Date.now(),
            primaryKey: 1,
            url: data.url || '/'
        }
    };
    event.waitUntil(self.registration.showNotification(data.title, options));
});

self.addEventListener('notificationclick', event => {
    event.notification.close();
    // Navigate to the URL if provided
    const urlToOpen = event.notification.data.url || '/';
    event.waitUntil(
        clients.matchAll({
            type: 'window',
            includeUncontrolled: true
        }).then(windowClients => {
            // Check if there is already a window/tab open with the target URL
            for (let client of windowClients) {
                if (client.url === urlToOpen && 'focus' in client) {
                    return client.focus();
                }
            }
            // If not, then open a new window/tab with the target URL
            if (clients.openWindow) {
                return clients.openWindow(urlToOpen);
            }
        })
    );
});