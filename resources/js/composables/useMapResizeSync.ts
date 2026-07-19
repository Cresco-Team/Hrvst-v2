import type { Map as LeafletMap } from 'leaflet'
import { onBeforeUnmount, type Ref, watch } from 'vue'

export function useMapResizeSync(
    container: Ref<HTMLElement | null>,
    getMap: () => LeafletMap | null,
): void {
    let observer: ResizeObserver | null = null

    watch(
        container,
        (el) => {
            observer?.disconnect()
            observer = null

            if (!el) return

            observer = new ResizeObserver(() => {
                getMap()?.invalidateSize()
            })
            observer.observe(el)
        },
        { immediate: true },
    )

    onBeforeUnmount(() => {
        observer?.disconnect()
        observer = null
    })
}
