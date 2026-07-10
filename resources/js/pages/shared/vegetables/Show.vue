<script setup lang="ts">
import { Deferred, Head, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import Heading from '@/components/Heading.vue'
import VegetableAnalyticsSummary from '@/components/shared/charts/VegetableAnalyticsSummary.vue'
import VegetableMonthlyChart from '@/components/shared/charts/VegetableMonthlyChart.vue'
import VegetableRecommendations from '@/components/shared/charts/VegetableRecommendations.vue'
import VegetableDayDetailSheet from '@/components/shared/VegetableDayDetailSheet.vue'
import VegetableMarketCalendar from '@/components/shared/VegetableMarketCalendar.vue'
import { Separator } from '@/components/ui/separator'
import { Skeleton } from '@/components/ui/skeleton'
import AppLayout from '@/layouts/AppLayout.vue'
import { useCapitalize } from '@/lib/utils'
import { categories, dashboard } from '@/routes'
import adminRoutes, { dashboard as adminDashboard } from '@/routes/admin'
import vegetables from '@/routes/vegetables'
import type { BreadcrumbItem, VegetableCalendarFilters, VegetableDaySchedule } from '@/types'
import type { VegetableResource } from '@/types/resources/product'

interface Props {
    vegetable?: VegetableResource
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
    const schedule = props.vegetable?.vegetable_calendar?.[dateStr]
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

                <template v-if="vegetable">
                    <!-- ── Analytics Summary ───────────────────────────────────────────── -->
                    <VegetableAnalyticsSummary
                        v-if="vegetable.analytics"
                        :analytics="vegetable.analytics"
                    />

                    <!-- ── Recommendations ────────────────────────────────────────────── -->
                    <VegetableRecommendations
                        v-if="vegetable.analytics?.recommendations.length"
                        :recommendations="vegetable.analytics.recommendations"
                    />

                    <Separator class="inline-block sm:hidden" />

                    <!-- ── Charts ─────────────────────────────────────────────────────── -->
                    <VegetableMonthlyChart
                        v-if="vegetable.monthly_activity?.length"
                        :monthly-activity="vegetable.monthly_activity"
                        :forecast="vegetable.analytics?.forecast"
                        :months-of-history="vegetable.analytics?.forecast"
                        :forecast-confidence="vegetable.analytics?.forecast_confidence"
                    />

                    <Separator class="inline-block sm:hidden" />

                    <!-- ── Market Calendar ─────────────────────────────────────────────── -->
                    <VegetableMarketCalendar
                        v-if="vegetable.vegetable_calendar"
                        :calendar="vegetable.vegetable_calendar"
                        :calendar-filters="calendarFilters"
                        :vegetable-id="meta.vegetableId"
                        @day-select="handleDaySelect"
                    />
                </template>
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
