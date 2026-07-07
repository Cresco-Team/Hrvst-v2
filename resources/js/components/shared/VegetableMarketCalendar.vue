<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3'
import { ChevronLeft, ChevronRight } from 'lucide-vue-next'
import { Calendar } from 'v-calendar'
import { computed } from 'vue'
import 'v-calendar/style.css'
import { Button } from '@/components/ui/button'
import { Card } from '@/components/ui/card'
import {
    BALANCE_DOT_CLASS,
    useCalendarBalance,
    type CalendarViewerRole,
} from '@/composables/useCalendarBalance'
import adminRoutes from '@/routes/admin'
import vegetables from '@/routes/vegetables'
import type { CalendarSlotData, VegetableCalendarFilters, VegetableDaySchedule } from '@/types'

interface Props {
    calendar?: Record<string, VegetableDaySchedule>
    calendarFilters: VegetableCalendarFilters
    vegetableId: number
}

const props = defineProps<Props>()

const emit = defineEmits<{
    'day-select': [dateStr: string]
}>()

// ─── Role + routing ───────────────────────────────────────────────────────────

const isAdmin = computed(() => usePage().props.auth.user.roles.includes('admin'))

function showRoute(): { url: string } {
    return isAdmin.value
        ? adminRoutes.vegetables.show(props.vegetableId)
        : vegetables.show(props.vegetableId)
}

// ─── Month navigation ─────────────────────────────────────────────────────────

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

    router.visit(showRoute().url, {
        data: { year, month },
        preserveState: true,
        preserveScroll: true,
        only: ['vegetable', 'calendarFilters'],
    })
}

function goToToday(): void {
    const now = new Date()
    router.visit(showRoute().url, {
        data: { year: now.getFullYear(), month: now.getMonth() + 1 },
        preserveState: true,
        preserveScroll: true,
        only: ['vegetable', 'calendarFilters'],
    })
}

// ─── VCalendar attributes ─────────────────────────────────────────────────────

const calendarAttributes = computed(() => {
    if (!props.calendar) return []

    return Object.entries(props.calendar).map(([dateStr, daySchedule]) => ({
        key: dateStr,
        dates: [new Date(`${dateStr}T00:00:00`)],
        customData: { dateStr, daySchedule },
    }))
})

// ─── Daily totals + balance classification ───────────────────────────────────

const dailyTotals = computed(() => {
    const map: Record<string, { supplyKg: number; demandKg: number }> = {}
    if (!props.calendar) return map

    for (const [dateStr, daySchedule] of Object.entries(props.calendar)) {
        let supplyKg = 0
        let demandKg = 0
        for (const slotData of Object.values(daySchedule) as CalendarSlotData[]) {
            supplyKg += slotData.supply_kg
            demandKg += slotData.demand_kg
        }
        if (supplyKg > 0 || demandKg > 0) map[dateStr] = { supplyKg, demandKg }
    }
    return map
})

const viewerRole = computed<CalendarViewerRole>(() => {
    const roles = usePage().props.auth.user.roles
    if (roles.includes('admin')) return 'admin'
    if (roles.includes('dealer')) return 'dealer'
    return 'farmer'
})

const { balanceFor, legend } = useCalendarBalance(dailyTotals, viewerRole)

function handleDayClick(day: { id: string }): void {
    if (!props.calendar?.[day.id]) return
    emit('day-select', day.id)
}
</script>

<template>
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-[220px_1fr]">
        <!-- Sidebar -->
        <div class="flex flex-col gap-4">
            <div class="flex flex-col gap-0.5">
                <h2 class="text-base font-medium">Market Calendar</h2>
                <p class="text-sm text-muted-foreground">
                    All scheduled supply and demand posts for this vegetable by date.
                </p>
            </div>

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

            <div class="flex flex-col gap-2 text-xs text-muted-foreground">
                <div v-for="item in legend" :key="item.label" class="flex items-center gap-1.5">
                    <span class="h-2 w-2 rounded-full" :class="BALANCE_DOT_CLASS[item.color]" />
                    {{ item.label }}
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
                        class="vc-day-tile flex h-full w-full cursor-pointer flex-col items-center justify-center gap-1 rounded-b-xs border bg-muted p-2 hover:bg-primary/10"
                        :class="{ 'opacity-30': !day.inMonth }"
                        @click="handleDayClick(day)"
                    >
                        <span class="text-xs leading-none font-semibold">{{ day.label }}</span>

                        <span
                            v-if="dailyTotals[day.id]"
                            class="size-2.5 rounded-full"
                            :class="BALANCE_DOT_CLASS[balanceFor(day.id)!.color]"
                            :title="balanceFor(day.id)!.label"
                        />
                    </div>
                </template>
            </Calendar>
        </Card>
    </div>
</template>

<style scoped>
:deep(.vc-day-content) { display: none; }
:deep(.vc-header) { display: none; }
:deep(.vc-dots), :deep(.vc-highlights) { display: none; }

:deep(.vc-container) {
    --vc-bg: hsl(var(--card));
    --vc-border: hsl(var(--border));
    --vc-header-title-color: hsl(var(--foreground));
    --vc-weekday-color: hsl(var(--muted-foreground));
    --vc-nav-hover-bg: hsl(var(--muted));
    --vc-day-content-hover-bg: transparent;
    --vc-accent-200: hsl(var(--primary) / 0.2);
    --vc-accent-600: hsl(var(--primary));
    background-color: hsl(var(--card));
    color: hsl(var(--foreground));
    border-color: transparent;
    width: 100% !important;
}

:deep(.vc-header .vc-title),
:deep(.vc-nav-title),
:deep(.vc-nav-item) { color: hsl(var(--foreground)); }

:deep(.vc-nav-item:hover),
:deep(.vc-nav-item.is-active) {
    background-color: hsl(var(--muted));
    color: hsl(var(--foreground));
}

:deep(.vc-arrow) { color: hsl(var(--muted-foreground)); }
:deep(.vc-arrow:hover) {
    background-color: hsl(var(--muted));
    color: hsl(var(--foreground));
}

:deep(.vc-pane-layout),
:deep(.vc-pane),
:deep(.vc-weeks) {
    width: 100%;
    min-width: 0;
}

:deep(.vc-week),
:deep(.vc-weeks) { gap: 2px; padding: 0; }

:deep(.vc-day) {
    min-height: 4rem;
    border: 1px solid hsl(var(--border) / 0.4);
    border-radius: 0.375rem;
    background-color: hsl(var(--muted) / 0.2);
    padding: 0;
    overflow: hidden;
    transition: background-color 0.15s;
}
:deep(.vc-day:hover) { background-color: hsl(var(--muted) / 0.5); }
:deep(.vc-day.is-today) {
    border-color: hsl(var(--primary) / 0.6);
    background-color: hsl(var(--primary) / 0.06);
}

@media (max-width: 479px) {
    :deep(.vc-day) { min-height: 2.75rem; }
    :deep(.vc-weekday) { font-size: 0.6rem; }
}
</style>
