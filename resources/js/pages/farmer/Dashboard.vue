<!-- resources/js/pages/farmer/Dashboard.vue -->
<script setup lang="ts">
import { Deferred, Head } from '@inertiajs/vue3'
import Heading from '@/components/Heading.vue'
import WasteRankingCard from '@/components/shared/charts/WasteRankingCard.vue'
import { Skeleton } from '@/components/ui/skeleton'
import AppLayout from '@/layouts/AppLayout.vue'
import farmer from '@/routes/farmer'
import type { BreadcrumbItem, FarmerDashboardProps } from '@/types'

defineProps<FarmerDashboardProps>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Farmer', href: farmer.dashboard().url },
    { title: 'Dashboard', href: farmer.dashboard().url },
]
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 lg:p-6">
            <Heading
                title="Dashboard"
                description="Market signals to guide what you plant next."
            />

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <Deferred data="topWastedDemand">
                    <template #fallback>
                        <Skeleton class="h-56 w-full rounded-xl" />
                    </template>

                    <WasteRankingCard
                        title="Most Unmet Demand"
                        description="Forecasted unmet demand for this month and the next 3, based on 5 years of seasonal trends."
                        :items="topWastedDemand"
                        unit-label="kg unmet"
                    />
                </Deferred>
            </div>
        </div>
    </AppLayout>
</template>
