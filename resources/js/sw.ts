import { CacheableResponsePlugin } from 'workbox-cacheable-response'
import { clientsClaim } from 'workbox-core'
import { ExpirationPlugin } from 'workbox-expiration'
import { registerRoute } from 'workbox-routing'
import { CacheFirst } from 'workbox-strategies'

declare let self: ServiceWorkerGlobalScope

// Activate this SW immediately on install/update instead of waiting for all
// tabs to close.
self.skipWaiting()
clientsClaim()

// NOTE: precacheAndRoute(self.__WB_MANIFEST) intentionally removed.
// vite-plugin-pwa's injectManifest strategy has a long-standing, unresolved
// bug generating precache URLs that don't respect Laravel's /build/ asset
// base — see vite-pwa/vite-plugin-pwa issues #396, #713, #263. Chasing that
// further isn't worth it: precaching only enables offline app-shell
// loading, which this Inertia app was never relying on anyway
// (navigateFallback was already null in the original config for exactly
// that reason). Push notifications don't need this — the listeners below
// are fully independent of precaching.

// ─── Runtime caching (unaffected by the above — this is a live network
// request rule, not manifest-based precaching) ──────────────────────────────

registerRoute(
    ({ url }) => url.hostname === 'fonts.googleapis.com',
    new CacheFirst({
        cacheName: 'google-fonts-cache',
        plugins: [
            new CacheableResponsePlugin({ statuses: [0, 200] }),
            new ExpirationPlugin({
                maxEntries: 10,
                maxAgeSeconds: 60 * 60 * 24 * 365,
            }),
        ],
    }),
)

// ─── Push notifications ────────────────────────────────────────────────────

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
