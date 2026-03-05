<script setup lang="ts">

import type { MarkerClusterGroup } from 'leaflet';
import type * as L from 'leaflet';
import { onMounted, watch } from 'vue'
import { useLeafletMap } from '@/composables/useLeafletMap'
import type { BarangayMarker, MapConfig } from '@/types/supply-map'

const props = defineProps<{
  markers: BarangayMarker[]
  config: MapConfig
}>()

const emit = defineEmits<{
  'marker-click': [marker: BarangayMarker]
}>()

const { container, init, getMap } = useLeafletMap({ zoom: props.config.zoom, placeFallbackMarker: false })

// Category → color mirrors the admin FarmerMap convention
const categoryColors: Record<string, string> = {
  'Leafy Greens':        '#10b981',
  'Root Vegetables':     '#f97316',
  'Brassicas':           '#14b8a6',
  'Bulb Vegetables':     '#eab308',
  'Legumes':             '#84cc16',
  'Fruiting Vegetables': '#dc2626',
  'Squash & Gourds':     '#ea580c',
}
const defaultColor = '#3b82f6'

function getDominantColor(breakdown: BarangayMarker['supply_breakdown']): string {
  if (!breakdown.length) return defaultColor
  const dominant = breakdown.reduce((a, b) => (a.count >= b.count ? a : b))
  return categoryColors[dominant.category] ?? defaultColor
}

function buildMarkerIcon(leaflet: typeof L, marker: BarangayMarker): L.DivIcon {
  const color = getDominantColor(marker.supply_breakdown)
  const count = marker.supply_count
  const size  = count < 5 ? 32 : count < 15 ? 38 : 44

  return leaflet.divIcon({
    html: `<div style="
      width:${size}px;height:${size}px;
      background:${color};
      border:3px solid white;
      border-radius:50%;
      box-shadow:0 3px 10px rgba(0,0,0,0.35);
      display:flex;align-items:center;justify-content:center;
      color:white;font-weight:700;font-size:${Math.floor(size / 3)}px;
      font-family:system-ui,sans-serif;
    ">${count}</div>`,
    className: 'supply-marker',
    iconSize:   [size, size],
    iconAnchor: [size / 2, size / 2],
  })
}

let clusterGroup: MarkerClusterGroup | null = null

async function renderMarkers(): Promise<void> {
  const map = getMap()
  if (!map) return

  const L = (await import('leaflet')).default
  await import('leaflet.markercluster/dist/MarkerCluster.css')
  await import('leaflet.markercluster/dist/MarkerCluster.Default.css')
  await import('leaflet.markercluster')

  if (clusterGroup) {
    clusterGroup.clearLayers()
  } else {
    clusterGroup = (L as any).markerClusterGroup({
      maxClusterRadius: 80,
      showCoverageOnHover: false,
      iconCreateFunction(cluster: any) {
        const count = cluster.getChildCount()
        const size  = count < 10 ? 40 : count < 30 ? 50 : 60
        return L.divIcon({
          html: `<div style="
            width:${size}px;height:${size}px;
            background:#3b82f6;
            border:3px solid white;border-radius:50%;
            box-shadow:0 3px 12px rgba(0,0,0,0.4);
            display:flex;align-items:center;justify-content:center;
            color:white;font-weight:700;font-size:${Math.floor(size / 3)}px;
            font-family:system-ui,sans-serif;
          ">${count}</div>`,
          className: 'supply-cluster',
          iconSize: [size, size],
        })
      },
    })
    map.addLayer(clusterGroup)
  }

  props.markers.forEach((markerData) => {
    const marker = L.marker(
      [markerData.coordinates.lat, markerData.coordinates.lng],
      { icon: buildMarkerIcon(L, markerData) },
    )
    marker.on('click', () => emit('marker-click', markerData))
    clusterGroup!.addLayer(marker)
  })
}

onMounted(async () => {
  await init(props.config.center.lat, props.config.center.lng)
  await renderMarkers()
})

watch(() => props.markers, renderMarkers, { deep: true })
</script>

<template>
  <div ref="container" class="h-full w-full rounded-lg overflow-hidden" />
</template>

<style scoped>
:deep(.supply-marker),
:deep(.supply-cluster) {
  background: transparent !important;
  border: none !important;
}

:deep(.leaflet-pane) {
  z-index: 1 !important;
}

:deep(.leaflet-top),
:deep(.leaflet-bottom) {
  z-index: 2 !important;
}
</style>
