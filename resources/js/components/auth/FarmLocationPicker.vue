<script setup lang="ts">
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

// Fix Leaflet's broken default icon paths under Vite
import markerIconUrl from 'leaflet/dist/images/marker-icon.png'
import markerShadowUrl from 'leaflet/dist/images/marker-shadow.png'
import { onMounted, onUnmounted, watch } from 'vue'
import InputError from '@/components/InputError.vue'

L.Marker.prototype.options.icon = L.icon({
	iconUrl: markerIconUrl,
	shadowUrl: markerShadowUrl,
	iconSize: [25, 41],
	iconAnchor: [12, 41],
	popupAnchor: [1, -34],
	shadowSize: [41, 41],
})

interface Coordinates {
	lat: number | null
	lng: number | null
}

interface MunicipalityCoords {
	lat: number
	lng: number
}

const props = defineProps<{
	modelValue: Coordinates
	municipalityCoords: MunicipalityCoords | null
	latError?: string
	lngError?: string
}>()

const emit = defineEmits<{
	'update:modelValue': [value: Coordinates]
}>()

let map: L.Map | null = null
let marker: L.Marker | null = null

const DEFAULT_CENTER: L.LatLngExpression = [16.4023, 120.596] // Benguet fallback
const DEFAULT_ZOOM = 13

function initMap(): void {
	const el = document.getElementById('farm-location-map')
	if (!el) return

	const center = props.municipalityCoords
		? ([
				props.municipalityCoords.lat,
				props.municipalityCoords.lng,
			] as L.LatLngExpression)
		: DEFAULT_CENTER

	map = L.map(el, { zoomControl: true }).setView(center, DEFAULT_ZOOM)

	L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
		attribution: '© OpenStreetMap contributors',
		maxZoom: 19,
	}).addTo(map)

	if (props.modelValue.lat !== null && props.modelValue.lng !== null) {
		placeMarker(props.modelValue.lat, props.modelValue.lng)
	}

	map.on('click', (e: L.LeafletMouseEvent) => {
		placeMarker(e.latlng.lat, e.latlng.lng)
		emit('update:modelValue', { lat: e.latlng.lat, lng: e.latlng.lng })
	})
}

function placeMarker(lat: number, lng: number): void {
	if (!map) return

	if (marker) {
		marker.setLatLng([lat, lng])
		return
	}

	marker = L.marker([lat, lng], { draggable: true }).addTo(map)
	marker.on('dragend', () => {
		const pos = marker!.getLatLng()
		emit('update:modelValue', { lat: pos.lat, lng: pos.lng })
	})
}

watch(
	() => props.municipalityCoords,
	(coords) => {
		if (!map || !coords) return
		map.setView([coords.lat, coords.lng], DEFAULT_ZOOM)
		if (marker) {
			marker.remove()
			marker = null
			emit('update:modelValue', { lat: null, lng: null })
		}
	},
)

onMounted(() => {
	initMap()
})
onUnmounted(() => {
	map?.remove()
	map = null
	marker = null
})
</script>

<template>
    <div class="grid gap-2">
        <!--
            isolation: isolate creates a new stacking context, trapping
            Leaflet's hardcoded z-indices inside this container so they
            don't bleed into the rest of the page layout.
        -->
        <div style="isolation: isolate;">
            <div
                id="farm-location-map"
                class="h-64 w-full rounded-md border"
                :class="{ 'border-destructive': latError || lngError }"
            />
        </div>
        <p class="text-xs text-muted-foreground">
            Click on the map to pin your farm's location. Drag the marker to adjust.
        </p>
        <InputError v-if="latError" :message="latError" />
        <InputError v-if="lngError" :message="lngError" />
        <p
            v-if="modelValue.lat !== null && modelValue.lng !== null"
            class="text-xs text-muted-foreground"
        >
            Pinned at: {{ modelValue.lat.toFixed(6) }}, {{ modelValue.lng.toFixed(6) }}
        </p>
    </div>
</template>
