<script setup lang="ts">
import { Deferred, Head } from '@inertiajs/vue3'
import Heading from '@/components/Heading.vue'
import WasteRankingCard from '@/components/shared/charts/WasteRankingCard.vue'
import { Skeleton } from '@/components/ui/skeleton'
import AppLayout from '@/layouts/AppLayout.vue'
import dealer from '@/routes/dealer'
import type { BreadcrumbItem, DealerDashboardProps } from '@/types'
import AnalyticsUpsellCard from '@/components/shared/charts/AnalyticsUpsellCard.vue'

defineProps<DealerDashboardProps>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dealer', href: dealer.dashboard().url },
    { title: 'Dashboard', href: dealer.dashboard().url },
]
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 lg:p-6">
            <Heading
                title="Dashboard"
                description="Market signals to guide your sourcing."
            />

            <AnalyticsUpsellCard v-if="analyticsLocked" :feature-label="upgradeFeatureLabel" />

            <div v-else class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <Deferred data="topWastedSupply">
                    <template #fallback>
                        <Skeleton class="h-56 w-full rounded-xl" />
                    </template>

                    <WasteRankingCard
                        title="Top Surplus Vegetables (Best to Buy)"
                        description="Predicted unsold supply over the next 4 months, based on 5-year historical trends."
                        :items="topWastedSupply"
                        :initial-visible="3"
                        unit-label="kg wasted"
                        guide-question="What's the most supplies vegetables?"
                    />
                </Deferred>

                <Deferred data="mostStableWastedSupply">
                    <template #fallback><Skeleton class="h-56 w-full rounded-xl" /></template>
                    <WasteRankingCard
                        title="Year-Round Surpluses (Best to Source)"
                        description="Items with steady extra supply all year long. A reliable option for continuous bulk buying."
                        :items="mostStableWastedSupply"
                        :initial-visible="3"
                        unit-label="kg/mo avg"
                        guide-question="What's the most supplies all year round?"
                    />
                </Deferred>
            </div>
        </div>
    </AppLayout>
</template>
