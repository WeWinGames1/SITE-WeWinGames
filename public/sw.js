// Import OneSignal Service Worker
importScripts("https://cdn.onesignal.com/sdks/web/v16/OneSignalSDK.sw.js");

// Your existing cache configuration
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