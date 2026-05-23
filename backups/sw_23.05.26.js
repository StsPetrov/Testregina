self.addEventListener('install', function() {
  self.skipWaiting();
});

self.addEventListener('activate', function() {
  self.registration.unregister().then(function() {
    return clients.matchAll({type: 'window'});
  }).then(function(clients) {
    clients.forEach(function(client) {
      client.navigate(client.url);
    });
  });
});

self.addEventListener('fetch', function(e) {
  if (e.request.method !== 'GET') return;
  
  var url = e.request.url;
  if (url.match(/\.(mp3|m4a|ogg|opus|flac|wav)(\?|$)/)) return;
  if (url.includes('admin-ajax.php')) return;
  if (url.includes('audiobook.1001ranobe.ru')) return;
  
  // Network First — сначала сеть, потом кеш
  e.respondWith(
    fetch(e.request).then(function(response) {
      if (response.status === 200 && response.type === 'basic') {
        var cloned = response.clone();
        caches.open('1001ranobe-v2').then(function(cache) {
          cache.put(e.request, cloned);
        });
      }
      return response;
    }).catch(function() {
      return caches.match(e.request);
    })
  );
});