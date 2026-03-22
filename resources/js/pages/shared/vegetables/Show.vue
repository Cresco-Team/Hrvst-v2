<script setup lang="ts">
import { Deferred, Head } from '@inertiajs/vue3'
import { Heart, ShoppingCart, Wheat } from 'lucide-vue-next'
import { computed } from 'vue'
import Heading from '@/components/Heading.vue'
import SmallCard from '@/components/shared/cards/SmallCard.vue'
import VegetableMonthlyChart from '@/components/shared/charts/VegetableMonthlyChart.vue'
import VegetablePriceChart from '@/components/shared/charts/VegetablePriceChart.vue'
import { Badge } from '@/components/ui/badge'
import { Skeleton } from '@/components/ui/skeleton'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import type { ShowVariety } from '@/types/shared/vegetables'

// ─── Props ────────────────────────────────────────────────────────────────────
// backHref/backLabel: role home (farmer → supplies, dealer → demands)
// indexHref: vegetables index URL for the mid-level breadcrumb
// Both injected by the controller — this component is role-agnostic.
interface Props {
    backHref: string
    backLabel: string
    indexHref: string
    variety?: ShowVariety | null
}

const props = defineProps<Props>()

// ─── Breadcrumbs — reactive so variety name appears once deferred prop loads ──
const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: props.backLabel, href: props.backHref },
    { title: 'Vegetables', href: props.indexHref },
    ...(props.variety
        ? [{
            title: `${props.variety.vegetable.name} ${props.variety.name}`,
            href: `${props.indexHref}/${props.variety.id}`,
        }]
        : []
    ),
])

// ─── Price freshness badge config ─────────────────────────────────────────────
const freshnessConfig = {
    recent: {
        label: 'Recently Updated',
        class: 'bg-green-500/10 text-green-700 dark:text-green-400 border-green-500/20',
    },
    stable: {
        label: 'Stable',
        class: 'bg-blue-500/10 text-blue-700 dark:text-blue-400 border-blue-500/20',
    },
    'very stable': {
        label: 'Older Price',
        class: 'bg-amber-500/10 text-amber-700 dark:text-amber-400 border-amber-500/20',
    },
    stale: {
        label: 'Stale Price',
        class: 'bg-red-500/10 text-red-700 dark:text-red-400 border-red-500/20',
    },
} as const
</script>

<template>

    <Head :title="variety ? `${variety.vegetable.name} ${variety.name}` : 'Variety'" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 lg:p-6">

            <Deferred data="variety">
                <template #fallback>
                    <div class="flex flex-col gap-6">
                        <Skeleton class="h-8 w-64" />
                        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                            <Skeleton v-for="i in 4" :key="i" class="h-24 rounded-xl" />
                        </div>
                        <Skeleton class="h-72 w-full rounded-xl" />
                        <Skeleton class="h-64 w-full rounded-xl" />
                    </div>
                </template>

                <template v-if="variety">

                    <!-- ── Header ──────────────────────────────────────────────────────── -->
                    <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                        <Heading :title="`${variety.vegetable.name} ${variety.name}`"
                            :description="variety.vegetable.category.name" />

                        <div class="flex items-center gap-2">
                            <Badge v-if="variety.latest_price" variant="outline"
                                :class="freshnessConfig[variety.latest_price.freshness]?.class">
                                {{ freshnessConfig[variety.latest_price.freshness]?.label }}
                            </Badge>

                            <span class="flex items-center gap-1 text-sm text-muted-foreground">
                                <Heart class="size-3.5" />
                                {{ variety.hearts_count }}
                            </span>
                        </div>
                    </div>

                    <!-- ── KPI row ─────────────────────────────────────────────────────── -->
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                        <SmallCard title="Min Price"
                            :value="variety.latest_price ? `₱${variety.latest_price.price_min.toFixed(2)}` : '—'"
                            value-class="text-green-600 dark:text-green-400" subtext="suggested minimum" />
                        <SmallCard title="Max Price"
                            :value="variety.latest_price ? `₱${variety.latest_price.price_max.toFixed(2)}` : '—'"
                            value-class="text-indigo-600 dark:text-indigo-400" subtext="suggested maximum" />
                        <SmallCard title="Active Supplies" :value="variety.supply_count" :icon="Wheat" />
                        <SmallCard title="Active Demands" :value="variety.demand_count" :icon="ShoppingCart" />
                    </div>

                    <!-- ── Price history chart ─────────────────────────────────────────── -->
                    <VegetablePriceChart :recent-prices="variety.recent_prices" />

                    <!-- ── Monthly market volume chart ────────────────────────────────── -->
                    <VegetableMonthlyChart :monthly-activity="variety.monthly_activity" />

                </template>
            </Deferred>

        </div>
    </AppLayout>
</template>
