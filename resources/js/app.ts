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
import { useRegisterSW } from 'virtual:pwa-register/vue'

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
		color: '#4B5563',
	},
})

useRegisterSW({
    onRegistered(r) {
        r && setInterval(() => r.update(), 60 * 60 * 1000) // check every hour
    },
})

initializeTheme()
