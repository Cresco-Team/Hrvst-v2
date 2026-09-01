import { wayfinder } from '@laravel/vite-plugin-wayfinder'
import tailwindcss from '@tailwindcss/vite'
import vue from '@vitejs/plugin-vue'
import laravel from 'laravel-vite-plugin'
import { defineConfig } from 'vite'
import { VitePWA } from 'vite-plugin-pwa'

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/app.ts'],
            ssr: 'resources/js/ssr.ts',
            refresh: true,
        }),
        tailwindcss(),
        wayfinder({
            formVariants: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        VitePWA({
            registerType: 'autoUpdate',
            injectRegister: null,

            // CHANGED: generateSW → injectManifest. Required to hand-write the
            // push/notificationclick listeners in sw.ts — generateSW gives no
            // hook for custom event handlers. See sw.ts for the SW source.
            strategies: 'injectManifest',
            srcDir: 'resources/js',
            filename: 'sw.ts',

            // REPLACES the old top-level `workbox: { globPatterns, navigateFallback }`
            // block — that key is silently ignored under injectManifest.
            // `navigateFallback: null` is dropped entirely: injectManifest never
            // auto-registers a NavigationRoute in the first place, so there was
            // nothing to opt out of — the Inertia-safe behavior you had before
            // is the default here, not something you have to ask for.
            // Precaching removed (see sw.ts for why). injectionPoint: undefined
            // tells workbox-build not to require or inject a self.__WB_MANIFEST
            // placeholder — without this, the build throws "Unable to find a
            // place to inject the manifest" since sw.ts no longer references it.
            injectManifest: {
                injectionPoint: undefined,
            },

            // REMOVED: the old `runtimeCaching` (Google Fonts CacheFirst rule)
            // also lived under `workbox: {}`, which injectManifest ignores.
            // It's been ported manually into sw.ts using workbox-routing +
            // workbox-strategies — same cache name, same expiration, same
            // behavior. Do not re-add a `workbox: {}` key here; it will be
            // silently dropped.

            manifest: {
                name: 'Hrvst',
                short_name: 'Hrvst',
                description: 'Hrvst Application',
                theme_color: '#ffffff',
                background_color: '#ffffff',
                display: 'standalone',
                orientation: 'portrait',
                scope: '/',
                start_url: '/dashboard',
                icons: [
                    { src: '/icons/pwa-192x192.png', sizes: '192x192', type: 'image/png' },
                    { src: '/icons/pwa-512x512.png', sizes: '512x512', type: 'image/png' },
                    { src: '/icons/pwa-512x512.png', sizes: '512x512', type: 'image/png', purpose: 'any maskable' },
                ],
            },
            devOptions: {
                enabled: true,
                type: 'module',
            },
        }),
    ],
    server: {
        allowedHosts: true,
    },
})