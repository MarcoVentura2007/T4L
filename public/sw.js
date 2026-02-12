// Service Worker per PWA Time4All
const CACHE_NAME = 'time4all-v1.0.0';
const urlsToCache = [
  '/',
  '/index.php',
  '/login.php',
  '/style.css',
  '/manifest.json',
  '/immagini/TIME4ALL_LOGO-removebg-preview.png',
  '/immagini/Icona.ico',
  '/immagini/profile-picture.png',
  '/immagini/Logo-centrodiurno.png',
  '/immagini/Logo-Cooperativa-Ergaterapeutica.png'
];

// Installazione del Service Worker
self.addEventListener('install', (event) => {
  console.log('Service Worker installing.');
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => {
        console.log('Opened cache');
        return cache.addAll(urlsToCache);
      })
  );
  // Forza l'attivazione immediata
  self.skipWaiting();
});

// Attivazione del Service Worker
self.addEventListener('activate', (event) => {
  console.log('Service Worker activating.');
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          if (cacheName !== CACHE_NAME) {
            console.log('Deleting old cache:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
  // Prendi il controllo di tutte le pagine aperte
  self.clients.claim();
});

// Gestione delle richieste - Cache First Strategy
self.addEventListener('fetch', (event) => {
  event.respondWith(
    caches.match(event.request)
      .then((response) => {
        // Cache hit - return response
        if (response) {
          return response;
        }

        // Clone the request
        const fetchRequest = event.request.clone();

        return fetch(fetchRequest).then(
          (response) => {
            // Check if we received a valid response
            if(!response || response.status !== 200 || response.type !== 'basic') {
              return response;
            }

            // Clone the response
            const responseToCache = response.clone();

            caches.open(CACHE_NAME)
              .then((cache) => {
                cache.put(event.request, responseToCache);
              });

            return response;
          }
        ).catch(() => {
          // Offline fallback - puoi aggiungere una pagina offline qui
          if (event.request.mode === 'navigate') {
            return caches.match('/index.php');
          }
        });
      })
  );
});

// Gestione delle notifiche push (per future implementazioni)
self.addEventListener('push', (event) => {
  console.log('Push message received', event);

  const options = {
    body: event.data ? event.data.text() : 'Nuova notifica da Time4All',
    icon: '/immagini/TIME4ALL_LOGO-removebg-preview.png',
    badge: '/immagini/Icona.ico',
    vibrate: [100, 50, 100],
    data: {
      dateOfArrival: Date.now(),
      primaryKey: 1
    }
  };

  event.waitUntil(
    self.registration.showNotification('Time4All', options)
  );
});

// Gestione click sulle notifiche
self.addEventListener('notificationclick', (event) => {
  console.log('Notification click received.');

  event.notification.close();

  event.waitUntil(
    clients.openWindow('/index.php')
  );
});
