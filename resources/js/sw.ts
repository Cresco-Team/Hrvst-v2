import { CacheableResponsePlugin } from 'workbox-cacheable-response'
import { clientsClaim } from 'workbox-core'
import { ExpirationPlugin } from 'workbox-expiration'
import { precacheAndRoute } from 'workbox-precaching'
import { registerRoute } from 'workbox-routing'
import { CacheFirst } from 'workbox-strategies'

declare let self: ServiceWorkerGlobalScope

// Activate this SW immediately on install/update instead of waiting for all
// tabs to close — matches typical PWA update-eagerly behavior. Drop these
// two lines if you're intentionally doing a "wait for reload" update flow.
self.skipWaiting()
clientsClaim()

// Required by injectManifest — vite-plugin-pwa replaces __WB_MANIFEST at
// build time with the precache list it would have generated for you under
// generateSW. Removing this line breaks offline/asset caching entirely.
precacheAndRoute(self.__WB_MANIFEST)

// ─── Runtime caching (ported from the old vite.config.ts workbox.runtimeCaching
// block — that key is silently ignored under injectManifest, so this has to
// live here instead. Same cache name, same 1-year/10-entry expiration.) ──────

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
