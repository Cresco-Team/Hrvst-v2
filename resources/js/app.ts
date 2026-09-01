import { createInertiaApp } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import type { DefineComponent } from 'vue'
import { createApp, h } from 'vue'
import '../css/app.css'
import { initializeTheme } from './composables/useAppearance'
import 'leaflet/dist/leaflet.css'
import 'leaflet.markercluster/dist/MarkerCluster.css'
import 'leaflet.markercluster/dist/MarkerCluster.Default.css'
import 'vue-sonner/style.css'

const appName = import.meta.env.VITE_APP_NAME || 'Hrvst'

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el)
    },
    progress: {
        color: '#51f0a8',
    },
})

// Manual registration only — no useRegisterSW / virtual:pwa-register import
// anywhere in this file. That helper independently registers /build/sw.js
// at /build/ scope using the plugin's own internal base/outDir resolution,
// which is exactly the wrong-scope registration this replaces. Do not
// reintroduce it alongside this block — the two conflict, and DevTools will
// keep showing the old /build/ scope from whichever one wins.
//
// Registers against the /sw.js Laravel route (routes/web.php), giving the
// worker root scope by default since the response is served at the literal
// root URL. Skipped in dev mode: injectManifest + devOptions serves a
// separate /dev-sw.js?dev-sw virtual URL in development that this app
// doesn't use — SW/push behavior is only tested against production builds.
console.log('[debug] import.meta.env.DEV =', import.meta.env.DEV)

if ('serviceWorker' in navigator && !import.meta.env.DEV) {
    window.addEventListener('load', async () => {
        try {
            const registration = await navigator.serviceWorker.register(
                '/sw.js',
                {
                    scope: '/',
                },
            )
            setInterval(() => registration.update(), 60 * 60 * 1000)
        } catch (error) {
            console.error('[SW] registration failed:', error)
        }
    })
}

initializeTheme()
