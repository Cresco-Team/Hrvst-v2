<script setup lang="ts">
import L from 'leaflet'
import { onMounted, onUnmounted, ref, watch } from 'vue'
import 'leaflet/dist/leaflet.css'
import 'leaflet.markercluster/dist/MarkerCluster.css'
import 'leaflet.markercluster/dist/MarkerCluster.Default.css'
import 'leaflet.markercluster'
import type { FarmerMarker } from '@/types/resources/marketplace'

interface MapCenter {
	lat: number
	lng: number
}

const props = defineProps<{
	markers: FarmerMarker[]
	center: MapCenter
	zoom: number
}>()

const emit = defineEmits<{
	'marker-click': [farmerId: number]
	'bounds-change': [bounds: { north: number; south: number; east: number; west: number }]
}>()

const mapContainer = ref<HTMLDivElement | null>(null)
let map: L.Map | null = null
let markerClusterGroup: L.MarkerClusterGroup | null = null

// Vegetable color mapping for different colored markers
const vegetableColors: Record<string, string> = {
	Lettuce: '#10b981',
	Spinach: '#059669',
	Cabbage: '#84cc16',
	Kale: '#22c55e',
	Carrot: '#f97316',
	Radish: '#ef4444',
	Potato: '#a16207',
	'Sweet Potato': '#ea580c',
	Tomato: '#dc2626',
	'Bell Pepper': '#eab308',
	Eggplant: '#7c3aed',
	Cucumber: '#16a34a',
	'Green Beans': '#65a30d',
	Peas: '#84cc16',
	Onion: '#d97706',
	Garlic: '#f59e0b',
	Broccoli: '#14b8a6',
	Cauliflower: '#94a3b8',
	'Brussels Sprouts': '#22c55e',
	Zucchini: '#059669',
	Pumpkin: '#f97316',
	'Butternut Squash': '#ea580c',
}

const getMarkerColor = (summary: FarmerMarker['supplies_summary']): string => {
	if (summary.length === 0) return '#6b7280'
	const dominant = summary.reduce((prev, current) =>
		prev.count > current.count ? prev : current,
	)
	return vegetableColors[dominant.vegetable] || '#6b7280'
}

const createCustomMarker = (marker: FarmerMarker): L.DivIcon => {
	const color = getMarkerColor(marker.supplies_summary)

	return L.divIcon({
		className: 'custom-marker',
		html: `
      <div style="
        width: 36px;
        height: 36px;
        background-color: ${color};
        border: 3px solid white;
        border-radius: 50%;
        box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: bold;
        font-size: 13px;
        cursor: pointer;
        transition: transform 0.2s;
      "
      onmouseover="this.style.transform='scale(1.2)'"
      onmouseout="this.style.transform='scale(1)'"
      >
        ${marker.ongoing_supplies_count}
      </div>
    `,
		iconSize: [36, 36],
		iconAnchor: [18, 18],
	})
}

const initMap = () => {
	if (!mapContainer.value || map) return

	map = L.map(mapContainer.value).setView([props.center.lat, props.center.lng], props.zoom)

	L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
		attribution: '© OpenStreetMap contributors',
		maxZoom: 19,
	}).addTo(map)

	markerClusterGroup = L.markerClusterGroup({
		maxClusterRadius: 60,
		disableClusteringAtZoom: 14,
		spiderfyOnMaxZoom: true,
		showCoverageOnHover: false,
		iconCreateFunction: (cluster) => {
			const count = cluster.getChildCount()
			const size = count < 10 ? 40 : count < 50 ? 50 : 60
			return L.divIcon({
				html: `<div style="width:${size}px;height:${size}px;background:#3b82f6;border:3px solid white;border-radius:50%;box-shadow:0 3px 12px rgba(0,0,0,0.4);display:flex;align-items:center;justify-content:center;color:white;font-weight:bold;font-size:${size / 3}px;">${count}</div>`,
				className: 'marker-cluster-custom',
				iconSize: [size, size],
			})
		},
	})

	map.addLayer(markerClusterGroup)

	map.on('moveend', () => {
		if (!map) return
		const bounds = map.getBounds()
		emit('bounds-change', {
			north: bounds.getNorth(),
			south: bounds.getSouth(),
			east: bounds.getEast(),
			west: bounds.getWest(),
		})
	})

	updateMarkers()
}

const updateMarkers = () => {
	if (!markerClusterGroup) return
	markerClusterGroup.clearLayers()
	props.markers.forEach((markerData) => {
		const marker = L.marker([markerData.coordinates.lat, markerData.coordinates.lng], {
			icon: createCustomMarker(markerData),
		})
		marker.on('click', () => emit('marker-click', markerData.id))
		markerClusterGroup!.addLayer(marker)
	})
}

watch(() => props.markers, updateMarkers, { deep: true })

onMounted(() => initMap())
onUnmounted(() => {
	if (map) {
		map.remove()
		map = null
	}
})
</script>

<template>
	<div ref="mapContainer" class="h-full w-full rounded-lg overflow-hidden" />
</template>

<style scoped>
:deep(.custom-marker),
:deep(.marker-cluster-custom) {
	background: transparent !important;
	border: none !important;
}

/* Ensure map stays below overlays */
:deep(.leaflet-pane) {
	z-index: 1 !important;
}

:deep(.leaflet-top),
:deep(.leaflet-bottom) {
	z-index: 2 !important;
}
</style>
