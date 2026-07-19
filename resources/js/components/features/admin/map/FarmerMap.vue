<script setup lang="ts">
import L from 'leaflet'
import { onMounted, onUnmounted, ref, watch } from 'vue'
import 'leaflet/dist/leaflet.css'
import type { FarmerMarker } from '@/types/resources/marketplace'
import { useMapResizeSync } from '@/composables/useMapResizeSync'

interface MapCenter {
    lat: number
    lng: number
}

interface FarmerGroup {
    key: number
    name: string
    lat: number
    lng: number
    farmers: FarmerMarker[]
    totalSupplies: number
    level: 'province' | 'municipality' | 'barangay'
}

const props = defineProps<{
    markers: FarmerMarker[]
    center: MapCenter
    zoom: number
}>()

const emit = defineEmits<{
    'barangay-click': [farmers: FarmerMarker[], barangayName: string]
    'bounds-change': [
        bounds: { north: number; south: number; east: number; west: number },
    ]
}>()

const mapContainer = ref<HTMLDivElement | null>(null)
let map: L.Map | null = null
let clusterLayers: L.Marker[] = []

const ZOOM_MUNICIPALITY = 10
const ZOOM_BARANGAY = 13

const COLORS = {
    province: '#8b5cf6',
    municipality: '#3b82f6',
    barangay: '#10b981',
} as const

function getLevel(zoom: number): FarmerGroup['level'] {
    if (zoom < ZOOM_MUNICIPALITY) return 'province'
    if (zoom < ZOOM_BARANGAY) return 'municipality'
    return 'barangay'
}

function centroid(farmers: FarmerMarker[]): [number, number] {
    const lat =
        farmers.reduce((s, f) => s + f.coordinates.lat, 0) / farmers.length
    const lng =
        farmers.reduce((s, f) => s + f.coordinates.lng, 0) / farmers.length
    return [lat, lng]
}

function buildGroups(level: FarmerGroup['level']): FarmerGroup[] {
    const buckets = new Map<number, FarmerMarker[]>()

    for (const f of props.markers) {
        const key =
            level === 'province'
                ? f.province_id
                : level === 'municipality'
                  ? f.municipality_id
                  : f.barangay_id

        if (!buckets.has(key)) buckets.set(key, [])
        buckets.get(key)!.push(f)
    }

    return Array.from(buckets.entries()).map(([key, farmers]) => {
        const [lat, lng] = centroid(farmers)
        const name =
            level === 'province'
                ? (farmers[0].province ?? `Province ${key}`)
                : level === 'municipality'
                  ? farmers[0].municipality
                  : (farmers[0].barangay ?? `Barangay ${key}`)

        return {
            key,
            name,
            lat,
            lng,
            farmers,
            totalSupplies: farmers.reduce(
                (s, f) => s + f.ongoing_supplies_count,
                0,
            ),
            level,
        }
    })
}

function makeIcon(group: FarmerGroup): L.DivIcon {
    const color = COLORS[group.level]
    const sz =
        group.level === 'province'
            ? 56
            : group.level === 'municipality'
              ? 48
              : 40
    const hint = group.level === 'barangay' ? 'view list' : 'zoom in'

    return L.divIcon({
        className: '',
        html: `<div style="
			width:${sz}px;height:${sz}px;
			background:${color};
			border:3px solid white;
			border-radius:50%;
			box-shadow:0 3px 10px rgba(0,0,0,.28);
			display:flex;flex-direction:column;
			align-items:center;justify-content:center;
			color:#fff;font-weight:700;
			cursor:pointer;
			transition:transform .15s;
		"
		onmouseover="this.style.transform='scale(1.12)'"
		onmouseout="this.style.transform='scale(1)'">
			<span style="font-size:${Math.max(10, sz / 3.5)}px;line-height:1">${group.farmers.length}</span>
			<span style="font-size:8px;opacity:.8;margin-top:1px">${hint}</span>
		</div>`,
        iconSize: [sz, sz],
        iconAnchor: [sz / 2, sz / 2],
    })
}

function refresh(): void {
    if (!map) return

    clusterLayers.forEach((m) => map!.removeLayer(m))
    clusterLayers = []

    if (props.markers.length === 0) return

    const level = getLevel(map.getZoom())
    const groups = buildGroups(level)

    for (const group of groups) {
        const marker = L.marker([group.lat, group.lng], {
            icon: makeIcon(group),
        })

        marker.bindTooltip(
            `<b>${group.name}</b><br>${group.farmers.length} farmer${group.farmers.length !== 1 ? 's' : ''} · ${group.totalSupplies} active`,
            { direction: 'top', offset: [0, -10] },
        )

        marker.on('click', () => {
            if (group.level === 'barangay') {
                emit('barangay-click', group.farmers, group.name)
                return
            }

            const bounds = L.latLngBounds(
                group.farmers.map(
                    (f) =>
                        [f.coordinates.lat, f.coordinates.lng] as [
                            number,
                            number,
                        ],
                ),
            )

            map!.flyToBounds(bounds, {
                padding: [50, 50],
                maxZoom:
                    group.level === 'province'
                        ? ZOOM_MUNICIPALITY + 1
                        : ZOOM_BARANGAY + 1,
            })
        })

        marker.addTo(map!)
        clusterLayers.push(marker)
    }
}

watch(() => props.markers, refresh, { deep: true })

onMounted(() => {
    if (!mapContainer.value) return

    map = L.map(mapContainer.value).setView(
        [props.center.lat, props.center.lng],
        props.zoom,
    )

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19,
    }).addTo(map)

    map.on('zoomend', refresh)
    map.on('moveend', () => {
        if (!map) return
        const b = map.getBounds()
        emit('bounds-change', {
            north: b.getNorth(),
            south: b.getSouth(),
            east: b.getEast(),
            west: b.getWest(),
        })
    })

    refresh()
})

useMapResizeSync(mapContainer, () => map)

onMounted(() => {
    if (!mapContainer.value) return

    map = L.map(mapContainer.value).setView(
        [props.center.lat, props.center.lng],
        props.zoom,
    )

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19,
    }).addTo(map)

    map.on('zoomend', refresh)
    map.on('moveend', () => {
        if (!map) return
        const b = map.getBounds()
        emit('bounds-change', {
            north: b.getNorth(),
            south: b.getSouth(),
            east: b.getEast(),
            west: b.getWest(),
        })
    })

    refresh()
})
</script>

<template>
    <div style="isolation: isolate;" class="h-full w-full">
        <div
            ref="mapContainer"
            class="h-full w-full overflow-hidden rounded-lg"
        />
    </div>
</template>
