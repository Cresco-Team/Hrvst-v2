import { ref, onBeforeUnmount } from 'vue'
import type { Map as LeafletMap, Marker } from 'leaflet'

export interface MapMarker {
  lat: number
  lng: number
  popup?: string
}

export interface UseLeafletMapOptions {
  zoom?: number
}

export function useLeafletMap(options: UseLeafletMapOptions = {}) {
  const { zoom = 14 } = options

  const container = ref<HTMLElement | null>(null)

  let map: LeafletMap | null = null
  let markers: Marker[] = []

  async function init(lat: number, lng: number, mapMarkers: MapMarker[] = []) {
    const L = (await import('leaflet')).default
    await import('leaflet/dist/leaflet.css')

    fixMarkerIcons(L)

    if (!container.value) return

    destroy()

    map = L.map(container.value, { zoomControl: true }).setView([lat, lng], zoom)

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
      maxZoom: 19,
    }).addTo(map)

    const targets = mapMarkers.length > 0 ? mapMarkers : [{ lat, lng }]

    for (const m of targets) {
      const marker = L.marker([m.lat, m.lng])
      if (m.popup) marker.bindPopup(m.popup)
      marker.addTo(map!)
      markers.push(marker)
    }
  }

  function destroy() {
    markers.forEach((m) => m.remove())
    markers = []
    map?.remove()
    map = null
  }

  function invalidateSize() {
    map?.invalidateSize()
  }

  onBeforeUnmount(destroy)

  return { container, init, destroy, invalidateSize }
}

// Vite breaks Leaflet's default marker asset resolution — this is required
function fixMarkerIcons(L: typeof import('leaflet')) {
  delete (L.Icon.Default.prototype as unknown as Record<string, unknown>)._getIconUrl

  L.Icon.Default.mergeOptions({
    iconUrl: new URL('leaflet/dist/images/marker-icon.png', import.meta.url).href,
    iconRetinaUrl: new URL('leaflet/dist/images/marker-icon-2x.png', import.meta.url).href,
    shadowUrl: new URL('leaflet/dist/images/marker-shadow.png', import.meta.url).href,
  })
}
