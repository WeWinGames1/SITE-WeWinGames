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
    // Skip the cache for requests to the API
  if (event.request.url.includes('/api/')) {
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
        icon: data.icon
      };
      event.waitUntil(self.registration.showNotification(data.title, options));
    });

    self.addEventListener('notificationclick', event => {
      event.notification.close();
      // Handle the click event, e.g., open a specific URL
    });