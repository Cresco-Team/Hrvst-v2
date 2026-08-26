// Import this into your injectManifest service worker entry (e.g. resources/js/sw.ts)
// after workbox's precache/routing setup. Not a standalone SW — a listener module.

self.addEventListener('push', (event) => {
    if (!event.data) return

    const payload = event.data.json()

    event.waitUntil(
        self.registration.showNotification(payload.title, {
            body: payload.body,
            icon: payload.icon ?? '/icons/pwa-192x192.png',
            badge: '/icons/pwa-192x192.png',
            data: { url: payload.data?.url ?? '/' },
        }),
    )
})

self.addEventListener('notificationclick', (event) => {
    event.notification.close()

    const targetUrl = event.notification.data?.url ?? '/'

    event.waitUntil(
        self.clients
            .matchAll({ type: 'window', includeUncontrolled: true })
            .then((clientList) => {
                for (const client of clientList) {
                    if (client.url === targetUrl && 'focus' in client) {
                        return client.focus()
                    }
                }
                return self.clients.openWindow(targetUrl)
            }),
    )
})
