<script setup lang="ts">
import { Deferred, Head, Link, usePage } from '@inertiajs/vue3'
import { Download, Lock } from '@lucide/vue'
import { computed, ref } from 'vue'
import { download } from '@/actions/App/Http/Controllers/VegetableExportController'
import Heading from '@/components/Heading.vue'
import AnalyticsUpsellCard from '@/components/shared/charts/AnalyticsUpsellCard.vue'
import VegetableAnalyticsSummary from '@/components/shared/charts/VegetableAnalyticsSummary.vue'
import VegetableMonthlyChart from '@/components/shared/charts/VegetableMonthlyChart.vue'
import VegetableRecommendations from '@/components/shared/charts/VegetableRecommendations.vue'
import VegetableDayDetailSheet from '@/components/shared/VegetableDayDetailSheet.vue'
import VegetableMarketCalendar from '@/components/shared/VegetableMarketCalendar.vue'
import AppTooltip from '@/components/templates/AppTooltip.vue'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import AppLayout from '@/layouts/AppLayout.vue'
import { useCapitalize } from '@/lib/utils'
import { categories, dashboard } from '@/routes'
import adminRoutes, { dashboard as adminDashboard } from '@/routes/admin'
import { show as billingShow } from '@/routes/billing'
import vegetables from '@/routes/vegetables'
import type { BreadcrumbItem, VegetableCalendarFilters, VegetableDaySchedule } from '@/types'
import type { VegetableDetailDataFixed } from '@/types/resources/product'

interface Props {
    vegetable?: VegetableDetailDataFixed
    calendarFilters: VegetableCalendarFilters
    meta: {
        vegetableId: number
        vegetableLabel: string
        categoryName: string
        categorySlug: string
    }
}

const props = defineProps<Props>()

const isAdmin = computed(() =>
    usePage().props.auth.user.roles.includes('admin'),
)

const breadcrumbs = computed<BreadcrumbItem[]>(() => {
    if (isAdmin.value) {
        return [
            { title: 'Admin', href: adminDashboard().url },
            { title: 'Vegetables', href: adminRoutes.categories.index().url },
            {
                title: props.meta.categoryName,
                href: adminRoutes.vegetables.index({
                    query: { category: props.meta.categorySlug },
                }).url,
            },
            {
                title: props.meta.vegetableLabel,
                href: adminRoutes.vegetables.show(props.meta.vegetableId).url,
            },
        ]
    }

    return [
        {
            title: useCapitalize(usePage().props.auth.user.roles[0]),
            href: dashboard().url,
        },
        { title: 'Vegetables', href: categories().url },
        {
            title: props.meta.categoryName,
            href: vegetables.index({
                query: { category: props.meta.categorySlug },
            }).url,
        },
        {
            title: props.meta.vegetableLabel,
            href: vegetables.show(props.meta.vegetableId).url,
        },
    ]
})

// ─── Day detail sheet ─────────────────────────────────────────────────────────

const sheetOpen = ref(false)
const selectedDateStr = ref<string | null>(null)
const selectedSchedule = ref<VegetableDaySchedule | null>(null)

const selectedDateLabel = computed(() => {
    if (!selectedDateStr.value) return ''
    return new Date(`${selectedDateStr.value}T00:00:00`).toLocaleDateString(
        'en-PH',
        {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric',
        },
    )
})

function handleDaySelect(dateStr: string): void {
    const schedule = props.vegetable?.vegetable_calendar?.[dateStr] as
        | VegetableDaySchedule
        | undefined
    if (!schedule) return

    selectedDateStr.value = dateStr
    selectedSchedule.value = schedule
    sheetOpen.value = true
}
</script>

<template>
    <Head :title="meta.vegetableLabel" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 lg:p-6">
            <Heading
                :title="meta.vegetableLabel"
                :description="meta.categoryName"
            />

            <Deferred data="vegetable">
                <template #fallback>
                    <div class="flex flex-col gap-6">
                        <Skeleton class="h-8 w-64" />
                        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                            <Skeleton
                                v-for="i in 4"
                                :key="i"
                                class="h-24 rounded-xl"
                            />
                        </div>
                        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                            <Skeleton class="h-72 rounded-xl" />
                            <Skeleton class="h-72 rounded-xl" />
                        </div>
                        <Skeleton class="h-72 w-full rounded-xl" />
                    </div>
                </template>

                <!-- Analytics summary + recommendations: free, always visible -->
                <VegetableAnalyticsSummary
                    v-if="vegetable?.analytics"
                    :analytics="vegetable.analytics"
                />

                <AppTooltip
                    v-if="vegetable?.forecast_locked"
                    content="Subscribe to export vegetable activity as CSV"
                >
                    <Button
                        as-child
                        variant="outline"
                        size="sm"
                        class="w-fit gap-1.5"
                    >
                        <Link :href="billingShow().url">
                            <Lock class="size-3.5" />
                            Export CSV
                        </Link>
                    </Button>
                </AppTooltip>

                <Button
                    v-else-if="vegetable"
                    as-child
                    variant="outline"
                    size="sm"
                    class="w-fit gap-1.5"
                >
                    <a :href="download(meta.vegetableId).url">
                        <Download class="size-4" />
                        Export CSV
                    </a>
                </Button>

                <VegetableRecommendations
                    v-if="vegetable?.analytics?.recommendations.length"
                    :recommendations="vegetable.analytics.recommendations"
                />

                <!-- Market calendar: free, always visible -->
                <VegetableMarketCalendar
                    v-if="vegetable"
                    :calendar="vegetable.vegetable_calendar"
                    :calendar-filters="calendarFilters"
                    :vegetable-id="meta.vegetableId"
                    @day-select="handleDaySelect"
                />

                <!-- Forecast: subscription-gated, teaser when locked -->
                <VegetableMonthlyChart
                    v-if="vegetable?.forecast"
                    :monthly-activity="vegetable.monthly_activity ?? []"
                    :forecast="vegetable.forecast.forecast"
                    :months-of-history="vegetable.forecast.months_of_history"
                    :forecast-confidence="vegetable.forecast.forecast_confidence"
                />
                <AnalyticsUpsellCard
                    v-else-if="vegetable?.forecast_locked"
                    title="6-Month Forecast Locked"
                    description="Subscribe to unlock seasonal demand and supply forecasting for this vegetable, based on 5 years of market history."
                    :feature-label="vegetable.upgrade_feature_label ?? 'Subscribe'"
                />
            </Deferred>
        </div>
    </AppLayout>

    <VegetableDayDetailSheet
        :open="sheetOpen"
        :date-label="selectedDateLabel"
        :schedule="selectedSchedule"
        @update:open="sheetOpen = $event"
    />
</template>
