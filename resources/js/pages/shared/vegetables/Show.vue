<script setup lang="ts">
import { Deferred, Head, router, usePage } from '@inertiajs/vue3'
import { Heart, ShoppingCart, Wheat, ChevronLeft, ChevronRight } from 'lucide-vue-next'
import { Calendar } from 'v-calendar'
import { computed, ref } from 'vue'
import 'v-calendar/style.css'
import Heading from '@/components/Heading.vue'
import SmallCard from '@/components/shared/cards/SmallCard.vue'
import VegetableMonthlyChart from '@/components/shared/charts/VegetableMonthlyChart.vue'
import VegetablePriceChart from '@/components/shared/charts/VegetablePriceChart.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Separator } from '@/components/ui/separator'
import {
    Sheet, SheetContent, SheetHeader, SheetTitle,
} from '@/components/ui/sheet'
import { Skeleton } from '@/components/ui/skeleton'
import AppLayout from '@/layouts/AppLayout.vue'
import dealer from '@/routes/dealer'
import farmer from '@/routes/farmer'
import type {
    BreadcrumbItem, CalendarTimeSlot, VarietyCalendarEntry, VarietyCalendarFilters, VarietyCalendarSummary, VarietyDaySchedule, VarietyMonthSchedule,
} from '@/types'
import type { VarietyResource } from '@/types/resources/product'

// ─── Props ────────────────────────────────────────────────────────────────────

interface Props {
    variety?: VarietyResource | null
    varietyCalendar?: VarietyMonthSchedule
    calendarSummary?: VarietyCalendarSummary
    calendarFilters: VarietyCalendarFilters
}

const props = defineProps<Props>()

// ─── Routing context ──────────────────────────────────────────────────────────

const page = usePage()
const isFarmer = page.props.auth.user.roles.includes('farmer')

const backHref = isFarmer ? farmer.supplies.index().url : dealer.demands.index().url
const indexHref = isFarmer ? farmer.vegetables.index().url : dealer.vegetables.index().url

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: isFarmer ? 'Farmer' : 'Dealer', href: backHref },
    { title: 'Vegetables', href: indexHref },
    ...(props.variety
        ? [{
            title: `${props.variety.vegetable?.name} ${props.variety.name}`,
            href: `${indexHref}/${props.variety.id}`,
        }]
        : []
    ),
])

// ─── Price freshness badge config ─────────────────────────────────────────────

const freshnessConfig = {
    recent: { label: 'Recently Updated', class: 'bg-green-500/10 text-green-700 dark:text-green-400 border-green-500/20' },
    stable: { label: 'Stable', class: 'bg-blue-500/10 text-blue-700 dark:text-blue-400 border-blue-500/20' },
    'very stable': { label: 'Older Price', class: 'bg-amber-500/10 text-amber-700 dark:text-amber-400 border-amber-500/20' },
    stale: { label: 'Stale Price', class: 'bg-red-500/10 text-red-700 dark:text-red-400 border-red-500/20' },
} as const

// ─── Calendar — month navigation ──────────────────────────────────────────────

const calendarYear = computed(() => props.calendarFilters.year)
const calendarMonth = computed(() => props.calendarFilters.month)

const calendarPage = computed(() => ({
    year: calendarYear.value,
    month: calendarMonth.value,
}))

const monthLabel = computed(() =>
    new Date(calendarYear.value, calendarMonth.value - 1, 1)
        .toLocaleString('en-PH', { month: 'long', year: 'numeric' })
)

function navigateMonth(direction: 1 | -1) {
    let month = calendarMonth.value + direction
    let year = calendarYear.value

    if (month > 12) { month = 1; year++ }
    if (month < 1) { month = 12; year-- }

    router.visit(`${indexHref}/${props.variety?.id}`, {
        data: { year, month },
        preserveState: true,
        preserveScroll: true,
        only: ['varietyCalendar', 'calendarSummary', 'calendarFilters'],
    })
}

function goToToday() {
    const now = new Date()
    router.visit(`${indexHref}/${props.variety?.id}`, {
        data: { year: now.getFullYear(), month: now.getMonth() + 1 },
        preserveState: true,
        preserveScroll: true,
        only: ['varietyCalendar', 'calendarSummary', 'calendarFilters'],
    })
}

// ─── Calendar — VCalendar attributes ─────────────────────────────────────────
//
// Each date with posts gets coloured dots:
//   green  = supply present
//   yellow = demand present
//
// `customData` carries the full day schedule for the detail sheet.

const calendarAttributes = computed(() => {
    if (!props.varietyCalendar) return []

    return Object.entries(props.varietyCalendar).map(([dateStr, daySchedule]) => {
        // Parse date as local time — appending T00:00:00 avoids UTC off-by-one day
        const date = new Date(`${dateStr}T00:00:00`)

        let hasSupply = false
        let hasDemand = false

        for (const entries of Object.values(daySchedule)) {
            for (const entry of entries) {
                if (entry.type === 'supply') hasSupply = true
                if (entry.type === 'demand') hasDemand = true
            }
        }

        const dots: Array<{ color: string }> = []
        if (hasSupply) dots.push({ color: 'green' })
        if (hasDemand) dots.push({ color: 'yellow' })

        return {
            key: dateStr,
            dot: dots.length === 1 ? dots[0] : dots,
            dates: date,
            customData: { dateStr, daySchedule },
        }
    })
})

// ─── Calendar — day click → detail sheet ─────────────────────────────────────

const sheetOpen = ref(false)
const selectedDateStr = ref<string | null>(null)
const selectedSchedule = ref<VarietyDaySchedule | null>(null)

const selectedDateLabel = computed(() => {
    if (!selectedDateStr.value) return ''
    const d = new Date(`${selectedDateStr.value}T00:00:00`)
    return d.toLocaleDateString('en-PH', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    })
})

function handleDayClick(day: { attributes: Array<{ customData?: { dateStr: string; daySchedule: VarietyDaySchedule } }> }) {
    const attr = day.attributes?.find(a => a.customData?.dateStr)
    if (!attr?.customData) return

    selectedDateStr.value = attr.customData.dateStr
    selectedSchedule.value = attr.customData.daySchedule
    sheetOpen.value = true
}

// ─── Sheet — time slot rendering ─────────────────────────────────────────────

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

function totalKgForSlot(entries: VarietyCalendarEntry[]): number {
    return entries.reduce((sum, e) => sum + e.total_kg, 0)
}

function formatKg(kg: number): string {
    return `${kg.toLocaleString('en-PH', { minimumFractionDigits: 0, maximumFractionDigits: 2 })} kg`
}
</script>

<template>

    <Head :title="variety ? `${variety.vegetable?.name} ${variety.name}` : 'Variety'" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 lg:p-6">

            <!-- ─── Variety detail (existing, unchanged) ──────────────────────── -->
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
                    <!-- Header -->
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

                    <!-- KPI row -->
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

                    <!-- Price history chart -->
                    <VegetablePriceChart :recent-prices="variety.recent_prices ?? []" />

                    <!-- Monthly market volume chart -->
                    <VegetableMonthlyChart :monthly-activity="variety.monthly_activity ?? []" />
                </template>
            </Deferred>

            <!-- ─── Market Calendar ───────────────────────────────────────────── -->
            <div class="flex flex-col gap-4">
                <div class="flex flex-col gap-0.5">
                    <h2 class="text-base font-medium">Market Calendar</h2>
                    <p class="text-sm text-muted-foreground">
                        All scheduled supply and demand posts for this variety by date.
                    </p>
                </div>

                <!-- Calendar summary stats -->
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <template v-if="!calendarSummary">
                        <Skeleton v-for="i in 4" :key="i" class="h-16 rounded-xl" />
                    </template>
                    <template v-else>
                        <div class="rounded-xl border bg-card px-4 py-3">
                            <p class="text-xs text-muted-foreground">Supply</p>
                            <p class="text-lg font-semibold text-emerald-600 dark:text-emerald-400 tabular-nums">
                                {{ formatKg(calendarSummary.total_supply_kg) }}
                            </p>
                        </div>
                        <div class="rounded-xl border bg-card px-4 py-3">
                            <p class="text-xs text-muted-foreground">Demand</p>
                            <p class="text-lg font-semibold text-amber-600 dark:text-amber-400 tabular-nums">
                                {{ formatKg(calendarSummary.total_demand_kg) }}
                            </p>
                        </div>
                        <div class="rounded-xl border bg-card px-4 py-3">
                            <p class="text-xs text-muted-foreground">Active Days</p>
                            <p class="text-lg font-semibold tabular-nums">{{ calendarSummary.active_days }}</p>
                        </div>
                        <div class="rounded-xl border bg-card px-4 py-3">
                            <p class="text-xs text-muted-foreground">Total Posts</p>
                            <p class="text-lg font-semibold tabular-nums">{{ calendarSummary.total_posts }}</p>
                        </div>
                    </template>
                </div>

                <!-- Month navigation -->
                <div class="flex items-center gap-2">
                    <Button variant="outline" size="icon" @click="navigateMonth(-1)" aria-label="Previous month">
                        <ChevronLeft class="size-4" />
                    </Button>
                    <span class="min-w-[10rem] text-center text-sm font-semibold tabular-nums">
                        {{ monthLabel }}
                    </span>
                    <Button variant="outline" size="icon" @click="navigateMonth(1)" aria-label="Next month">
                        <ChevronRight class="size-4" />
                    </Button>
                    <Button variant="ghost" size="sm" class="text-xs text-muted-foreground" @click="goToToday">
                        Today
                    </Button>
                </div>

                <!-- VCalendar -->
                <div class="rounded-xl border bg-card p-4">
                    <template v-if="!varietyCalendar">
                        <Skeleton class="h-72 w-full rounded-lg" />
                    </template>
                    <template v-else>
                        <Calendar :attributes="calendarAttributes" :initial-page="calendarPage"
                            :key="`${calendarYear}-${calendarMonth}`" expanded @dayclick="handleDayClick" />
                    </template>
                </div>

                <!-- Legend -->
                <div class="flex flex-wrap items-center gap-4 text-xs text-muted-foreground">
                    <div class="flex items-center gap-1.5">
                        <span class="h-2 w-2 rounded-full bg-emerald-500" />
                        Supply
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="h-2 w-2 rounded-full bg-yellow-400" />
                        Demand
                    </div>
                    <Separator orientation="vertical" class="h-4" />
                    <div v-for="slot in TIME_SLOTS" :key="slot.key" class="flex items-center gap-1.5">
                        <span class="h-2 w-2 rounded-full" :class="slot.dotClass" />
                        {{ slot.label }}
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>

    <!-- ─── Day detail sheet ───────────────────────────────────────────────── -->
    <Sheet v-model:open="sheetOpen">
        <SheetContent class="w-full sm:max-w-md overflow-y-auto">
            <SheetHeader class="mb-4">
                <SheetTitle class="text-base font-semibold">{{ selectedDateLabel }}</SheetTitle>
            </SheetHeader>

            <div v-if="selectedSchedule" class="flex flex-col gap-5">
                <template v-for="slot in TIME_SLOTS" :key="slot.key">
                    <div v-if="selectedSchedule[slot.key]?.length">
                        <!-- Slot heading -->
                        <div class="flex items-center gap-2 mb-3">
                            <span class="h-2.5 w-2.5 rounded-full shrink-0" :class="slot.dotClass" />
                            <span class="text-sm font-semibold">{{ slot.label }}</span>
                            <span class="ml-auto text-xs text-muted-foreground tabular-nums">
                                {{ formatKg(totalKgForSlot(selectedSchedule[slot.key]!)) }} total
                            </span>
                        </div>

                        <div class="flex flex-col gap-2 pl-4">
                            <div v-for="(entry, idx) in selectedSchedule[slot.key]" :key="idx"
                                class="flex items-center justify-between rounded-lg border bg-muted/30 px-3 py-2">
                                <div class="flex items-center gap-2">
                                    <span class="rounded-full px-2 py-0.5 text-[10px] font-bold uppercase" :class="entry.type === 'supply'
                                        ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300'
                                        : 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300'">
                                        {{ entry.type }}
                                    </span>
                                    <span class="text-xs text-muted-foreground">
                                        {{ entry.posts_count }} {{ entry.posts_count === 1 ? 'post' : 'posts' }}
                                    </span>
                                </div>
                                <span class="text-sm font-semibold tabular-nums">
                                    {{ formatKg(entry.total_kg) }}
                                </span>
                            </div>
                        </div>

                        <Separator class="mt-4" />
                    </div>
                </template>
            </div>

            <p v-else class="text-sm text-muted-foreground">
                No schedule data for this day.
            </p>
        </SheetContent>
    </Sheet>
</template>
