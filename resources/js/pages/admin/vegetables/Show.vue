<script setup lang="ts">
import { Deferred, Head, router } from '@inertiajs/vue3'
import { Heart, MapPin, ShoppingCart, Wheat } from 'lucide-vue-next'
import { Calendar } from 'v-calendar'
import { computed, ref } from 'vue'
import Heading from '@/components/Heading.vue'
import SmallCard from '@/components/shared/cards/SmallCard.vue'
import VegetableMonthlyChart from '@/components/shared/charts/VegetableMonthlyChart.vue'
import VegetablePriceChart from '@/components/shared/charts/VegetablePriceChart.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Skeleton } from '@/components/ui/skeleton'
import {
	Table,
	TableBody,
	TableCell,
	TableHead,
	TableHeader,
	TableRow,
} from '@/components/ui/table'
import AppLayout from '@/layouts/AppLayout.vue'
import { dashboard } from '@/routes/admin'
import {
	index as vegetablesIndex,
	show as vegetablesShow,
} from '@/routes/admin/vegetables/varieties'
import type {
	BreadcrumbItem,
	CalendarTimeSlot,
	PriceFreshness,
	VarietyCalendarEntry,
	VarietyCalendarFilters,
	VarietyDaySchedule,
	VarietyMonthSchedule,
	VarietyResource,
} from '@/types'

const props = defineProps<{
	variety?: VarietyResource
	varietyCalendar?: VarietyMonthSchedule
	calendarFilters: VarietyCalendarFilters
}>()

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
	{ title: 'Admin', href: dashboard().url },
	{ title: 'Vegetables', href: vegetablesIndex().url },
	...(props.variety
		? [
				{
					title: `${props.variety.vegetable?.name} ${props.variety.name}`,
					href: vegetablesShow(props.variety.id).url,
				},
			]
		: []),
])

const calendarYear = computed(() => props.calendarFilters.year)
const calendarMonth = computed(() => props.calendarFilters.month)

const calendarPage = computed(() => ({ year: calendarYear.value, month: calendarMonth.value }))

const monthLabel = computed(() =>
	new Date(calendarYear.value, calendarMonth.value - 1, 1).toLocaleString('en-PH', {
		month: 'long',
		year: 'numeric',
	}),
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

	router.visit(vegetablesShow(props.variety.id).url, {
		data: { year, month },
		preserveState: true,
		preserveScroll: true,
		only: ['varietyCalendar', 'calendarSummary', 'calendarFilters'],
	})
}

function goToToday(): void {
	const now = new Date()
	router.visit(vegetablesShow(props.variety.id), {
		data: { year: now.getFullYear(), month: now.getMonth() + 1 },
		preserveState: true,
		preserveScroll: true,
		only: ['varietyCalendar', 'calendarSummary', 'calendarFilters'],
	})
}

// ─── Calendar — VCalendar attributes ─────────────────────────────────────────

const calendarAttributes = computed(() => {
	if (!props.variety?.variety_calendar) return []

	return Object.entries(props.variety.variety_calendar).map(([dateStr, daySchedule]) => {
		const date = new Date(`${dateStr}T00:00:00`)

		return {
			key: dateStr,
			dates: [date],
			customData: { dateStr, daySchedule },
		}
	})
})

// ─── Calendar — day detail sheet ─────────────────────────────────────────────

const sheetOpen = ref(false)
const selectedDateStr = ref<string | null>(null)
const selectedSchedule = ref<VarietyDaySchedule | null>(null)

const selectedDateLabel = computed(() => {
	if (!selectedDateStr.value) return ''
	return new Date(`${selectedDateStr.value}T00:00:00`).toLocaleDateString('en-PH', {
		weekday: 'long',
		year: 'numeric',
		month: 'long',
		day: 'numeric',
	})
})

function handleDayClick(day: {
	attributes: Array<{ customData?: { dateStr: string; daySchedule: VarietyDaySchedule } }>
}): void {
	const attr = day.attributes?.find((a) => a.customData?.dateStr)
	if (!attr?.customData) return

	selectedDateStr.value = attr.customData.dateStr
	selectedSchedule.value = attr.customData.daySchedule
	sheetOpen.value = true
}

// ─── Calendar — time slot config ─────────────────────────────────────────────

const TIME_SLOTS: Array<{ key: CalendarTimeSlot; label: string; dotClass: string }> = [
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

// ─── Calendar — inline day totals ─────────────────────────────────────────────

const dailyTotals = computed(() => {
	const map: Record<string, { supplyKg: number; demandKg: number }> = {}
	if (!props.variety?.variety_calendar) return map

	for (const [dateStr, daySchedule] of Object.entries(props.variety.variety_calendar)) {
		let supplyKg = 0
		let demandKg = 0
		for (const entries of Object.values(daySchedule)) {
			for (const entry of entries) {
				if (entry.type === 'supply') supplyKg += entry.total_kg
				else if (entry.type === 'demand') demandKg += entry.total_kg
			}
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
	return max || 1 // avoid division by zero
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
                        <Skeleton class="h-48 w-full rounded-xl" />
                        <Skeleton class="h-64 w-full rounded-xl" />
                    </div>
                </template>

                    <Heading :title="`${variety?.vegetable?.name} ${variety?.name}`"
                        :description="variety?.vegetable?.category?.name" />

                    
                    <!-- Summary -->
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                        <SmallCard 
                            title="Min Price"
                            :value="variety?.latest_price ? `₱${variety?.latest_price.price_min.toFixed(2)}` : '—'"
                            value-class="text-green-600 dark:text-green-400" 
                            subtext="suggested minimum" 
                        />
                        <SmallCard 
                            title="Max Price"
                            :value="variety?.latest_price ? `₱${variety?.latest_price.price_max.toFixed(2)}` : '—'"
                            value-class="text-indigo-600 dark:text-indigo-400" 
                            subtext="suggested maximum" 
                        />
                        <SmallCard 
                            title="Monthly Supply" 
                            :value="variety?.monthly_supply_kg" 
                        />
                        <SmallCard 
                            title="Monthy Demand" 
                            :value="variety?.monthly_demand_kg" 
                        />
                    </div>

                    <!-- Charts -->
                     <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                        <VegetablePriceChart v-if="variety?.recent_prices" :recent-prices="variety?.recent_prices" />
                        <VegetableMonthlyChart v-if="variety?.monthly_activity?.length" :monthly-activity="variety?.monthly_activity" />
                     </div>

                    <!-- <Card v-if="variety?.supply_municipalities?.length">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2 text-sm font-semibold">
                                <MapPin class="size-4 text-primary" />
                                Ongoing Supply by Municipality
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Municipality</TableHead>
                                        <TableHead class="text-right">Total (kg)</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="entry in variety?.supply_municipalities" :key="entry.name">
                                        <TableCell class="font-medium">{{ entry.name }}</TableCell>
                                        <TableCell class="text-right font-mono tabular-nums">
                                            {{ entry.total_kg.toLocaleString() }}
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card> -->

                    <!-- Calendar -->
                     <div class="grid grid-cols-1 gap-4 lg:grid-cols-[220px_1fr]">
                        <div class="flex flex-col gap-4">
                            <div class="flex flex-col gap-0.5">
                                <h2 class="text-base font-medium">Market Calendar</h2>
                                <p class="text-sm text-muted-foreground">
                                    All scheduled supply and demand posts for this variety by date.
                                </p>
                            </div>

                            <!-- Month navigation -->
                            <div class="flex items-center gap-2">
                                <Button variant="outline" size="icon" aria-label="Previous month" @click="navigateMonth(-1)">
                                    <ChevronLeft class="size-4" />
                                </Button>
                                <span class="min-w-[5rem] text-center text-sm font-semibold tabular-nums">
                                    {{ monthLabel }}
                                </span>
                                <Button variant="outline" size="icon" aria-label="Next month" @click="navigateMonth(1)">
                                    <ChevronRight class="size-4" />
                                </Button>
                            </div>
                            <Button variant="ghost" size="sm" class="w-fit text-xs text-muted-foreground" @click="goToToday">
                                Today
                            </Button>

                            <!-- Legend -->
                            <div class="flex flex-col gap-2 text-xs text-muted-foreground">
                                <div class="flex items-center gap-1.5">
                                    <span class="h-2 w-2 rounded-full bg-emerald-500" />
                                    Supply
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <span class="h-2 w-2 rounded-full bg-yellow-400" />
                                    Demand
                                </div>
                                
                                <Separator class="my-1" />
                                
                                <div v-for="slot in TIME_SLOTS" :key="slot.key" class="flex items-center gap-1.5">
                                    <span class="h-2 w-2 rounded-full" :class="slot.dotClass" />
                                    {{ slot.label }}
                                </div>
                            </div>
                            </div>

                            <!-- Calendar grid — deferred -->
                            <Deferred data="variety">
                            <template #fallback>
                                <Skeleton class="h-72 w-full rounded-xl" />
                            </template>

                            <div class="rounded-xl border bg-card p-2 sm:p-4">
                                <Calendar
                                    :key="`${calendarYear}-${calendarMonth}`"
                                    :attributes="calendarAttributes"
                                    :initial-page="calendarPage"
                                    expanded
                                    @dayclick="handleDayClick"
                                >
                                <template #day-content="{ day }">
                                    <div
                                        class="vc-day-tile flex h-full w-full cursor-pointer flex-col p-1"
                                        :class="{ 'opacity-30': !day.inMonth }"
                                    >
                                    <!-- Day number -->
                                    <span class="mb-auto text-xs font-semibold leading-none">{{ day.label }}</span>

                                    <!-- Bars + labels -->
                                    <template v-if="dailyTotals[day.id]">
                                        <div class="mt-1 flex flex-col gap-0.5">
                                        <!-- Supply bar -->
                                        <template v-if="dailyTotals[day.id].supplyKg">
                                            <div class="flex items-center gap-1">
                                                <div class="relative h-1.5 flex-1 overflow-hidden rounded-full bg-emerald-100 dark:bg-emerald-950">
                                                    <div
                                                        class="absolute inset-y-0 left-0 rounded-full bg-emerald-500"
                                                        :style="{ width: barPct(dailyTotals[day.id].supplyKg) }"
                                                    />
                                                </div>
                                            </div>
                                            <span class="text-[9px] leading-none text-emerald-600 dark:text-emerald-400">
                                            S {{ formatKgShort(dailyTotals[day.id].supplyKg) }}kg
                                            </span>
                                        </template>

                                        <!-- Demand bar -->
                                        <template v-if="dailyTotals[day.id].demandKg">
                                            <div class="flex items-center gap-1">
                                                <div class="relative h-1.5 flex-1 overflow-hidden rounded-full bg-amber-100 dark:bg-amber-950">
                                                    <div
                                                        class="absolute inset-y-0 left-0 rounded-full bg-amber-500"
                                                        :style="{ width: barPct(dailyTotals[day.id].demandKg) }"
                                                    />
                                                </div>
                                            </div>
                                            <span class="text-[9px] leading-none text-amber-600 dark:text-amber-400">
                                                D {{ formatKgShort(dailyTotals[day.id].demandKg) }}kg
                                            </span>
                                        </template>
                                        </div>
                                    </template>
                                    </div>
                                </template>
                                </Calendar>
                            </div>
                            </Deferred>
                     </div>

            </Deferred>

        </div>
    </AppLayout>
</template>
