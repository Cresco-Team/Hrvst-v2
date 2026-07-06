import { ref, watch } from 'vue'
import { slotSummary } from '@/actions/App/Http/Controllers/Api/VegetableAvailabilityController'

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

export function netKgClass(netKg: number): string {
    if (netKg > 0) return 'text-destructive'
    if (netKg < 0) return 'text-orange-500'
    return 'text-muted-foreground'
}

export function formatNetKg(net: number): string {
    const abs = Math.abs(net)
    const formatted = abs.toLocaleString('en-PH', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    })
    if (net > 0) return `${formatted} kg surplus`
    if (net < 0) return `${formatted} kg unmet`
    return 'Balanced'
}

export function useVegetableAvailability(
    getDate: () => string,
    getTimeSlot: () => string,
    getVegetableIds: () => string[],
) {
    const cache = ref<Record<string, AvailabilityState>>({})

    function cacheKey(vegetableId: string): string {
        return `${vegetableId}:${getDate()}:${getTimeSlot()}`
    }

    function getState(vegetableId: string): AvailabilityState {
        if (!vegetableId || !getDate()) return { status: 'idle' }
        return cache.value[cacheKey(vegetableId)] ?? { status: 'idle' }
    }

    function getData(vegetableId: string): AvailabilityData | null {
        const state = getState(vegetableId)
        return state.status === 'loaded' ? state.data : null
    }

    async function fetchOne(vegetableId: string): Promise<void> {
        if (!vegetableId || !getDate()) return

        const key = cacheKey(vegetableId)
        const existing = cache.value[key]
        if (existing?.status === 'loaded' || existing?.status === 'loading') return

        cache.value[key] = { status: 'loading' }

        try {
            const params: Record<string, string> = { date: getDate() }
            const slot = getTimeSlot()
            if (slot) params.time_slot = slot

            const url = slotSummary(Number(vegetableId), { query: params }).url
            const res = await fetch(url, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            })
            if (!res.ok) throw new Error(`HTTP ${res.status}`)

            cache.value[key] = { status: 'loaded', data: await res.json() }
        } catch {
            cache.value[key] = { status: 'error' }
        }
    }

    watch(getVegetableIds, (newIds, oldIds = []) => {
        newIds.forEach((id, index) => {
            if (id && id !== oldIds[index]) void fetchOne(id)
        })
    })

    watch([getDate, getTimeSlot], () => {
        getVegetableIds().filter(Boolean).forEach((id) => void fetchOne(id))
    })

    return { getState, getData }
}
