<script setup lang="ts">
import { Deferred, Head, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import Heading from '@/components/Heading.vue'
import VegetableAnalyticsSummary from '@/components/shared/charts/VegetableAnalyticsSummary.vue'
import VegetableRecommendations from '@/components/shared/charts/VegetableRecommendations.vue'
import VegetableMonthlyChart from '@/components/shared/charts/VegetableMonthlyChart.vue'
import VegetableMarketCalendar from '@/components/shared/VegetableMarketCalendar.vue'
import DetailSheet from '@/components/dialogs/DetailSheet.vue'
import { Separator } from '@/components/ui/separator'
import { Skeleton } from '@/components/ui/skeleton'
import AppLayout from '@/layouts/AppLayout.vue'
import { useCapitalize } from '@/lib/utils'
import { categories, dashboard } from '@/routes'
import adminRoutes, { dashboard as adminDashboard } from '@/routes/admin'
import vegetables from '@/routes/vegetables'
import type {
    BreadcrumbItem,
    CalendarTimeSlot,
    VegetableCalendarFilters,
    VegetableDaySchedule,
} from '@/types'
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

const TIME_SLOTS: Array<{
    key: CalendarTimeSlot
    label: string
    dotClass: string
}> = [
    { key: 'morning', label: 'Morning (6 AM – 12 PM)', dotClass: 'bg-amber-400' },
    { key: 'afternoon', label: 'Afternoon (12 PM – 6 PM)', dotClass: 'bg-emerald-500' },
    { key: 'evening', label: 'Evening (6 PM – 10 PM)', dotClass: 'bg-indigo-500' },
    { key: 'unscheduled', label: 'No time slot', dotClass: 'bg-slate-400' },
]

function formatKg(kg: number): string {
    return `${kg.toLocaleString('en-PH', { minimumFractionDigits: 0, maximumFractionDigits: 2 })} kg`
}

function formatNetBadge(net: number): string {
    const abs = Math.abs(net)
    const formatted = abs.toLocaleString('en-PH', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    })
    if (net > 0) return `+${formatted} kg surplus`
    if (net < 0) return `${formatted} kg unmet`
    return 'Balanced'
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

                    <!-- ── Charts ─────────────────────────────────────────────────────── -->
                    <VegetableMonthlyChart
                        v-if="vegetable.monthly_activity?.length"
                        :monthly-activity="vegetable.monthly_activity"
                        :forecast="vegetable.analytics?.forecast"
                    />

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

    <!-- ─── Day detail sheet ──────────────────────────────────────────────────── -->
    <DetailSheet
        :open="sheetOpen"
        :title="selectedDateLabel"
        @update:open="sheetOpen = $event"
    >
        <div v-if="selectedSchedule" class="flex flex-col gap-6">
            <template v-for="slot in TIME_SLOTS" :key="slot.key">
                <div v-if="selectedSchedule[slot.key]">
                    <!-- Slot header -->
                    <div class="mb-3 flex items-center gap-2">
                        <span
                            class="h-2.5 w-2.5 shrink-0 rounded-full"
                            :class="slot.dotClass"
                        />
                        <span class="text-sm font-semibold">{{
                            slot.label
                        }}</span>
                    </div>

                    <!-- Supply / Demand / Net summary -->
                    <div
                        class="mb-4 flex flex-col gap-1.5 rounded-lg border bg-muted/20 p-3 pl-4 text-xs"
                    >
                        <div class="flex items-center justify-between">
                            <span
                                class="flex items-center gap-1.5 text-muted-foreground"
                            >
                                <span
                                    class="h-1.5 w-1.5 rounded-full bg-emerald-500"
                                />
                                Supply
                            </span>
                            <span
                                class="font-semibold text-emerald-600 tabular-nums dark:text-emerald-400"
                            >
                                {{
                                    formatKg(
                                        selectedSchedule[slot.key]!.supply_kg,
                                    )
                                }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span
                                class="flex items-center gap-1.5 text-muted-foreground"
                            >
                                <span
                                    class="h-1.5 w-1.5 rounded-full bg-amber-500"
                                />
                                Demand
                            </span>
                            <span
                                class="font-semibold text-amber-600 tabular-nums dark:text-amber-400"
                            >
                                {{
                                    formatKg(
                                        selectedSchedule[slot.key]!.demand_kg,
                                    )
                                }}
                            </span>
                        </div>
                        <Separator class="my-0.5" />
                        <div class="flex items-center justify-between">
                            <span class="font-medium">Net</span>
                            <span
                                class="rounded-full px-2 py-0.5 text-[10px] font-bold tabular-nums"
                                :class="
                                    selectedSchedule[slot.key]!.net_kg > 0
                                        ? 'bg-destructive/20 text-destructive dark:bg-destructive/40'
                                        : selectedSchedule[slot.key]!.net_kg < 0
                                          ? 'bg-orange-100 text-orange-700'
                                          : 'bg-muted text-muted-foreground'
                                "
                            >
                                {{
                                    formatNetBadge(
                                        selectedSchedule[slot.key]!.net_kg,
                                    )
                                }}
                            </span>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <p v-else class="text-sm text-muted-foreground">
            No schedule data for this day.
        </p>
    </DetailSheet>
</template>
