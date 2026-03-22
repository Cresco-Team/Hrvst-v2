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
import dealer from '@/routes/dealer'
import type { BreadcrumbItem, DealerVegetableShowProps, PriceFreshness } from '@/types'

const props = defineProps<DealerVegetableShowProps>()

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: 'Dealer', href: dealer.demands.index().url },
    { title: 'Vegetables', href: dealer.vegetables.index().url },
    ...(props.variety
        ? [{
            title: `${props.variety.vegetable?.name} ${props.variety.name}`,
            href: dealer.vegetables.show(props.variety.id).url,
        }]
        : []),
])

const freshnessConfig: Record<PriceFreshness, { label: string; class: string }> = {
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
}
</script>

<template>

    <Head :title="variety ? `${variety.vegetable?.name} ${variety.name}` : 'Variety'" />

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

                    <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                        <Heading :title="`${variety.vegetable?.name} ${variety.name}`"
                            :description="variety.vegetable?.category?.name" />
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

                    <VegetablePriceChart v-if="variety.recent_prices" :recent-prices="variety.recent_prices" />

                    <VegetableMonthlyChart v-if="variety.monthly_activity"
                        :monthly-activity="variety.monthly_activity" />

                </template>
            </Deferred>

        </div>
    </AppLayout>
</template>
