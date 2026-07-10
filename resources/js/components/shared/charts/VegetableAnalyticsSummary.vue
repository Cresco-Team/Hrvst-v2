<script setup lang="ts">
import { computed } from 'vue'
import SmallCard from '@/components/shared/cards/SmallCard.vue'
import type { VarietyAnalytics } from '@/types/resources/product'

const props = defineProps<{
    analytics: VarietyAnalytics
}>()

// ── Market balance ────────────────────────────────────────────────────────────

const bandConfig = computed(() => {
    switch (props.analytics.imbalance_band) {
        case 'oversupply':
            return {
                label: 'Oversupply',
                valueClass: 'text-red-600 dark:text-red-400',
            }
        case 'undersupply':
            return {
                label: 'Undersupply',
                valueClass: 'text-amber-600 dark:text-amber-400',
            }
        default:
            return {
                label: 'Balanced',
                valueClass: 'text-green-600 dark:text-green-400',
            }
    }
})

const ratioLabel = computed(() => {
    const r = props.analytics.supply_demand_ratio
    const pct = Math.round(Math.abs(r) * 100)
    if (r > 0) return `+${pct}% excess supply`
    if (r < 0) return `${pct}% excess demand`
    return 'supply meets demand'
})

// ── Fulfillment rates ─────────────────────────────────────────────────────────

function fulfillmentConfig(rate: number | null) {
    if (rate === null) return { valueClass: 'text-muted-foreground' }
    if (rate >= 0.7) return { valueClass: 'text-green-600 dark:text-green-400' }
    if (rate >= 0.5) return { valueClass: 'text-amber-600 dark:text-amber-400' }
    return { valueClass: 'text-red-600 dark:text-red-400' }
}

function formatRate(rate: number | null): string {
    return rate !== null ? `${Math.round(rate * 100)}%` : '—'
}

const supplyConfig = computed(() =>
    fulfillmentConfig(props.analytics.supply_fulfillment_rate),
)
const demandConfig = computed(() =>
    fulfillmentConfig(props.analytics.demand_fulfillment_rate),
)
</script>

<template>
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
        <!-- Market Balance -->
        <SmallCard
            title="Market Balance"
            :value="bandConfig.label"
            :value-class="bandConfig.valueClass"
            :subtext="ratioLabel"
            subtext-below
            class="col-span-2 sm:col-span-1"
        />

        <!-- Supply Fulfillment -->
        <SmallCard
            title="Supply Fulfillment"
            :value="formatRate(analytics.supply_fulfillment_rate)"
            :value-class="supplyConfig.valueClass"
            subtext="3-month avg"
            subtext-below
        />

        <!-- Demand Fulfillment -->
        <SmallCard
            title="Demand Fulfillment"
            :value="formatRate(analytics.demand_fulfillment_rate)"
            :value-class="demandConfig.valueClass"
            subtext="3-month avg"
            subtext-below
        />
    </div>
</template>
