self.addEventListener('push', function (event) {
  if (!event.data) return

  try {
    const data = event.data.json()
    const title = data.title || 'Fitnez'
    const options = {
      body: data.body || '',
      icon: '/favicon.ico',
      badge: '/favicon.ico',
      vibrate: [200, 100, 200],
    }
    event.waitUntil(self.registration.showNotification(title, options))
  } catch {
    // ignore
  }
})

self.addEventListener('notificationclick', function (event) {
  event.notification.close()
  const urlToOpen = new URL('/', self.location.origin)
  event.waitUntil(clients.openWindow(urlToOpen.href))
})
