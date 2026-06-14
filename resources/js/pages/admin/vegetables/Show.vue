<script setup lang="ts">
import { Deferred, Head, router } from '@inertiajs/vue3'
import { ChevronLeft, ChevronRight } from 'lucide-vue-next'
import { Calendar } from 'v-calendar'
import 'v-calendar/style.css'
import { computed, ref } from 'vue'
import Heading from '@/components/Heading.vue'
import VarietyAnalyticsSummary from '@/components/shared/charts/VarietyAnalyticsSummary.vue'
import VarietyRecommendations from '@/components/shared/charts/VarietyRecommendations.vue'
import VarietyMonthlyChart from '@/components/shared/charts/VegetableMonthlyChart.vue'
import VarietyPriceChart from '@/components/shared/charts/VegetablePriceChart.vue'
import { Button } from '@/components/ui/button'
import { Separator } from '@/components/ui/separator'
import { Skeleton } from '@/components/ui/skeleton'
import AppLayout from '@/layouts/AppLayout.vue'
import admin, { dashboard } from '@/routes/admin'
import { show as vegetablesShow } from '@/routes/admin/vegetables/varieties'
import type {
    BreadcrumbItem,
    CalendarSlotData,
    CalendarTimeSlot,
    VarietyCalendarFilters,
    VarietyDaySchedule,
    VarietyResource,
} from '@/types'
import DetailSheet from '@/components/dialogs/DetailSheet.vue'

const props = defineProps<{
    variety?: VarietyResource
    calendarFilters: VarietyCalendarFilters
    meta: {
        varietyId: number
        varietyLabel: string
        categoryName: string
        categorySlug: string
    }
}>()

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: 'Admin', href: dashboard().url },
    { title: 'Vegetables', href: admin.categories.index().url },
    {
        title: props.meta.categoryName,
        href: admin.vegetables.index({
            query: { category: props.meta.categorySlug },
        }).url,
    },
    {
        title: props.meta.varietyLabel,
        href: admin.vegetables.varieties.show(props.meta.varietyId).url,
    },
])

// ─── Calendar — month navigation ──────────────────────────────────────────────

const calendarYear = computed(() => props.calendarFilters.year)
const calendarMonth = computed(() => props.calendarFilters.month)
const calendarPage = computed(() => ({
    year: calendarYear.value,
    month: calendarMonth.value,
}))

const monthLabel = computed(() =>
    new Date(calendarYear.value, calendarMonth.value - 1, 1).toLocaleString(
        'en-PH',
        {
            month: 'long',
            year: 'numeric',
        },
    ),
)

function navigateMonth(direction: 1 | -1): void {
    let month = calendarMonth.value + direction
    let year = calendarYear.value

    if (month > 12) {
        month = 1
        year++
    }
    if (month < 1) {
        month = 12
        year--
    }

    router.visit(vegetables.show(props.meta.varietyId).url, {
        data: { year, month },
        preserveState: true,
        preserveScroll: true,
        only: ['variety', 'calendarFilters'],
    })
}

function goToToday(): void {
    const now = new Date()
    router.visit(vegetables.show(props.meta.varietyId).url, {
        data: { year: now.getFullYear(), month: now.getMonth() + 1 },
        preserveState: true,
        preserveScroll: true,
        only: ['variety', 'calendarFilters'],
    })
}

// ─── Calendar — VCalendar attributes ─────────────────────────────────────────

const calendarAttributes = computed(() => {
    if (!props.variety?.variety_calendar) return []

    return Object.entries(props.variety.variety_calendar).map(
        ([dateStr, daySchedule]) => ({
            key: dateStr,
            dates: [new Date(`${dateStr}T00:00:00`)],
            customData: { dateStr, daySchedule },
        }),
    )
})

// ─── Calendar — day detail sheet ─────────────────────────────────────────────

const sheetOpen = ref(false)
const selectedDateStr = ref<string | null>(null)
const selectedSchedule = ref<VarietyDaySchedule | null>(null)

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

function handleDayClick(day: { id: string }): void {
    const schedule = props.variety?.variety_calendar?.[day.id]
    if (!schedule) return

    selectedDateStr.value = day.id
    selectedSchedule.value = schedule
    sheetOpen.value = true
}

// ─── Calendar — time slot config ─────────────────────────────────────────────

const TIME_SLOTS: Array<{
    key: CalendarTimeSlot
    label: string
    dotClass: string
}> = [
    {
        key: 'morning',
        label: 'Morning (6 AM – 12 PM)',
        dotClass: 'bg-amber-400',
    },
    {
        key: 'afternoon',
        label: 'Afternoon (12 PM – 6 PM)',
        dotClass: 'bg-emerald-500',
    },
    {
        key: 'evening',
        label: 'Evening (6 PM – 10 PM)',
        dotClass: 'bg-indigo-500',
    },
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

// ─── Calendar — inline day totals ─────────────────────────────────────────────

const dailyTotals = computed(() => {
    const map: Record<string, { supplyKg: number; demandKg: number }> = {}
    if (!props.variety?.variety_calendar) return map

    for (const [dateStr, daySchedule] of Object.entries(
        props.variety.variety_calendar,
    )) {
        let supplyKg = 0
        let demandKg = 0
        for (const slotData of Object.values(
            daySchedule,
        ) as CalendarSlotData[]) {
            supplyKg += slotData.supply_kg
            demandKg += slotData.demand_kg
        }
        if (supplyKg > 0 || demandKg > 0) map[dateStr] = { supplyKg, demandKg }
    }
    return map
})

const maxDailyKg = computed(() => {
    let max = 0
    for (const { supplyKg, demandKg } of Object.values(dailyTotals.value)) {
        if (supplyKg > max) max = supplyKg
        if (demandKg > max) max = demandKg
    }
    return max || 1
})

function barPct(kg: number): string {
    return `${Math.round((kg / maxDailyKg.value) * 100)}%`
}

function formatKgShort(kg: number): string {
    if (kg >= 1000) return `${(kg / 1000).toFixed(1)}t`
    return kg % 1 === 0 ? `${kg}` : `${kg.toFixed(1)}`
}
</script>

<template>
    <Head
        :title="
            variety ? `${variety.vegetable?.name} ${variety.name}` : 'Variety'
        "
    />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 lg:p-6">
            <Heading
                :title="meta.varietyLabel"
                :description="meta.categoryName"
            />

            <Deferred data="variety">
                <template #fallback>
                    <div class="flex flex-col gap-6">
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

                <template v-if="variety">
                    <!-- ── Analytics Summary ───────────────────────────────────────────── -->
                    <VarietyAnalyticsSummary
                        v-if="variety.analytics"
                        :analytics="variety.analytics"
                    />

                    <!-- ── Recommendations ────────────────────────────────────────────── -->
                    <VarietyRecommendations
                        v-if="variety.analytics?.recommendations.length"
                        :recommendations="variety.analytics.recommendations"
                    />

                    <!-- ── Charts ─────────────────────────────────────────────────────── -->
                    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                        <VarietyPriceChart
                            v-if="variety.recent_prices?.length"
                            :recent-prices="variety.recent_prices"
                        />
                        <VarietyMonthlyChart
                            v-if="variety.monthly_activity?.length"
                            :monthly-activity="variety.monthly_activity"
                        />
                    </div>

                    <!-- ── Market Calendar ─────────────────────────────────────────────── -->
                    <div
                        class="grid grid-cols-1 gap-4 lg:grid-cols-[220px_1fr]"
                    >
                        <!-- Sidebar -->
                        <div class="flex flex-col gap-4">
                            <div class="flex flex-col gap-0.5">
                                <h2 class="text-base font-medium">
                                    Market Calendar
                                </h2>
                                <p class="text-sm text-muted-foreground">
                                    All scheduled supply and demand posts for
                                    this variety by date.
                                </p>
                            </div>

                            <div class="flex items-center gap-2">
                                <Button
                                    variant="outline"
                                    size="icon"
                                    aria-label="Previous month"
                                    @click="navigateMonth(-1)"
                                >
                                    <ChevronLeft class="size-4" />
                                </Button>
                                <span
                                    class="min-w-[5rem] text-center text-sm font-semibold tabular-nums"
                                >
                                    {{ monthLabel }}
                                </span>
                                <Button
                                    variant="outline"
                                    size="icon"
                                    aria-label="Next month"
                                    @click="navigateMonth(1)"
                                >
                                    <ChevronRight class="size-4" />
                                </Button>
                            </div>

                            <Button
                                variant="ghost"
                                size="sm"
                                class="w-fit text-xs text-muted-foreground"
                                @click="goToToday"
                            >
                                Today
                            </Button>

                            <div
                                class="flex flex-col gap-2 text-xs text-muted-foreground"
                            >
                                <div class="flex items-center gap-1.5">
                                    <span
                                        class="h-2 w-2 rounded-full bg-emerald-500"
                                    />
                                    Supply
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <span
                                        class="h-2 w-2 rounded-full bg-yellow-400"
                                    />
                                    Demand
                                </div>
                                <Separator class="my-1" />
                                <div
                                    v-for="slot in TIME_SLOTS"
                                    :key="slot.key"
                                    class="flex items-center gap-1.5"
                                >
                                    <span
                                        class="h-2 w-2 rounded-full"
                                        :class="slot.dotClass"
                                    />
                                    {{ slot.label }}
                                </div>
                            </div>
                        </div>

                        <!-- Calendar grid -->
                        <Card class="p-2">
                            <Calendar
                                :key="`${calendarYear}-${calendarMonth}`"
                                :attributes="calendarAttributes"
                                :initial-page="calendarPage"
                                expanded
                            >
                                <template #day-content="{ day }">
                                    <div
                                        class="vc-day-tile flex h-full w-full cursor-pointer flex-col p-1"
                                        :class="{ 'opacity-30': !day.inMonth }"
                                        @click="handleDayClick(day)"
                                    >
                                        <span
                                            class="mb-auto text-xs leading-none font-semibold"
                                            >{{ day.label }}</span
                                        >

                                        <template v-if="dailyTotals[day.id]">
                                            <div
                                                class="mt-1 flex flex-col gap-0.5"
                                            >
                                                <template
                                                    v-if="
                                                        dailyTotals[day.id]
                                                            .supplyKg
                                                    "
                                                >
                                                    <div
                                                        class="flex items-center gap-1"
                                                    >
                                                        <div
                                                            class="relative h-1.5 flex-1 overflow-hidden rounded-full bg-emerald-100 dark:bg-emerald-950"
                                                        >
                                                            <div
                                                                class="absolute inset-y-0 left-0 rounded-full bg-emerald-500"
                                                                :style="{
                                                                    width: barPct(
                                                                        dailyTotals[
                                                                            day
                                                                                .id
                                                                        ]
                                                                            .supplyKg,
                                                                    ),
                                                                }"
                                                            />
                                                        </div>
                                                    </div>
                                                    <span
                                                        class="text-[9px] leading-none text-emerald-600 dark:text-emerald-400"
                                                    >
                                                        S
                                                        {{
                                                            formatKgShort(
                                                                dailyTotals[
                                                                    day.id
                                                                ].supplyKg,
                                                            )
                                                        }}kg
                                                    </span>
                                                </template>

                                                <template
                                                    v-if="
                                                        dailyTotals[day.id]
                                                            .demandKg
                                                    "
                                                >
                                                    <div
                                                        class="flex items-center gap-1"
                                                    >
                                                        <div
                                                            class="relative h-1.5 flex-1 overflow-hidden rounded-full bg-amber-100 dark:bg-amber-950"
                                                        >
                                                            <div
                                                                class="absolute inset-y-0 left-0 rounded-full bg-amber-500"
                                                                :style="{
                                                                    width: barPct(
                                                                        dailyTotals[
                                                                            day
                                                                                .id
                                                                        ]
                                                                            .demandKg,
                                                                    ),
                                                                }"
                                                            />
                                                        </div>
                                                    </div>
                                                    <span
                                                        class="text-[9px] leading-none text-amber-600 dark:text-amber-400"
                                                    >
                                                        D
                                                        {{
                                                            formatKgShort(
                                                                dailyTotals[
                                                                    day.id
                                                                ].demandKg,
                                                            )
                                                        }}kg
                                                    </span>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </Calendar>
                        </Card>
                    </div>
                </template>
            </Deferred>
        </div>
    </AppLayout>

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
                                <span class="text-muted-foreground/60"
                                    >({{
                                        selectedSchedule[slot.key]!
                                            .supply_posts_count
                                    }}
                                    posts)</span
                                >
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
                                <span class="text-muted-foreground/60"
                                    >({{
                                        selectedSchedule[slot.key]!
                                            .demand_posts_count
                                    }}
                                    posts)</span
                                >
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
                                        ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300'
                                        : selectedSchedule[slot.key]!.net_kg < 0
                                          ? 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300'
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

                    <!-- Individual items -->
                    <div class="flex flex-col gap-2 pl-4">
                        <div
                            v-for="(item, idx) in selectedSchedule[slot.key]!
                                .items"
                            :key="idx"
                            class="flex items-center justify-between rounded-lg border bg-muted/30 px-3 py-2"
                        >
                            <div class="flex min-w-0 items-center gap-2">
                                <span
                                    class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-bold uppercase"
                                    :class="
                                        item.type === 'supply'
                                            ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300'
                                            : 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300'
                                    "
                                >
                                    {{ item.type }}
                                </span>
                                <span class="truncate text-xs font-medium">{{
                                    item.variety_name
                                }}</span>
                            </div>
                            <div
                                class="ml-2 flex shrink-0 flex-col items-end gap-0.5"
                            >
                                <span
                                    class="text-sm font-semibold tabular-nums"
                                    >{{ formatKg(item.quantity_kg) }}</span
                                >
                            </div>
                        </div>
                    </div>

                    <Separator class="mt-4" />
                </div>
            </template>
        </div>

        <p v-else class="text-sm text-muted-foreground">
            No schedule data for this day.
        </p>
    </DetailSheet>
</template>

<style scoped>
:deep(.vc-day-content) {
    display: none;
}
:deep(.vc-dots),
:deep(.vc-highlights) {
    display: none;
}
:deep(.vc-day) {
    min-height: 5rem;
    border: 1px solid hsl(var(--border) / 0.4);
    border-radius: 0.375rem;
    background-color: hsl(var(--muted) / 0.2);
    padding: 0;
    overflow: hidden;
    transition: background-color 0.15s;
}
:deep(.vc-day:hover) {
    background-color: hsl(var(--muted) / 0.5);
}
:deep(.vc-day.is-today) {
    border-color: hsl(var(--primary) / 0.6);
    background-color: hsl(var(--primary) / 0.06);
}
:deep(.vc-week),
:deep(.vc-weeks) {
    gap: 3px;
    padding: 0;
}
</style>
