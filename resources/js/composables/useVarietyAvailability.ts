import { ref, watch } from 'vue'
import { slotSummary } from '@/actions/App/Http/Controllers/Api/VarietyAvailabilityController'

export interface AvailabilityData {
    supply_kg: number
    demand_kg: number
    net_kg: number
}

type AvailabilityState =
    | { status: 'idle' }
    | { status: 'loading' }
    | { status: 'loaded'; data: AvailabilityData }
    | { status: 'error' }

/**
 * Tailwind colour class for a net_kg value.
 * Positive = oversupply (green), negative = undersupply (red), zero = neutral.
 */
export function netKgClass(netKg: number): string {
    if (netKg > 0) return 'text-emerald-600 dark:text-emerald-400'
    if (netKg < 0) return 'text-destructive'
    return 'text-muted-foreground'
}

/** "+50.0 kg" / "-12.5 kg" / "0.0 kg" */
export function formatNetKg(netKg: number): string {
    return `${netKg > 0 ? '+' : ''}${netKg.toFixed(1)} kg`
}

/**
 * Reactive slot-availability lookup for form items.
 *
 * - Cache key: `varietyId:date:timeSlot` — stale entries are never served
 *   when date or slot changes because the key changes with them.
 * - `getVarietyIds` watcher fires per-row: only the changed variety is fetched,
 *   not the whole list.
 * - `[getDate, getTimeSlot]` watcher refetches all currently selected varieties
 *   whenever the schedule context changes.
 */
export function useVarietyAvailability(
    getDate: () => string,
    getTimeSlot: () => string,
    getVarietyIds: () => string[],
) {
    const cache = ref<Record<string, AvailabilityState>>({})

    function cacheKey(varietyId: string): string {
        return `${varietyId}:${getDate()}:${getTimeSlot()}`
    }

    function getState(varietyId: string): AvailabilityState {
        if (!varietyId || !getDate()) return { status: 'idle' }
        return cache.value[cacheKey(varietyId)] ?? { status: 'idle' }
    }

    /** Returns loaded data or null — avoids discriminated-union gymnastics in templates. */
    function getData(varietyId: string): AvailabilityData | null {
        const state = getState(varietyId)
        return state.status === 'loaded' ? state.data : null
    }

    async function fetchOne(varietyId: string): Promise<void> {
        if (!varietyId || !getDate()) return

        const key = cacheKey(varietyId)
        const existing = cache.value[key]

        // Skip if a request for this exact key is already in-flight or resolved.
        if (existing?.status === 'loaded' || existing?.status === 'loading') return

        cache.value[key] = { status: 'loading' }

        try {
            const params: Record<string, string> = { date: getDate() }
            const slot = getTimeSlot()
            if (slot) params.time_slot = slot

            const url = slotSummary(Number(varietyId), { query: params }).url

            const res = await fetch(url, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            })

            if (!res.ok) throw new Error(`HTTP ${res.status}`)

            const data: AvailabilityData = await res.json()
            cache.value[key] = { status: 'loaded', data }
        } catch {
            cache.value[key] = { status: 'error' }
        }
    }

    // Per-row: only fetch the variety that actually changed.
    watch(getVarietyIds, (newIds, oldIds = []) => {
        newIds.forEach((id, index) => {
            if (id && id !== oldIds[index]) void fetchOne(id)
        })
    })

    // Bulk: when date or slot changes the cache key changes for every variety,
    // so the guard in fetchOne will allow fresh requests for all of them.
    watch([getDate, getTimeSlot], () => {
        getVarietyIds()
            .filter(Boolean)
            .forEach((id) => void fetchOne(id))
    })

    return { getState, getData }
}
