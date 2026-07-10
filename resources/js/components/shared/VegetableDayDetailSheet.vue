<script setup lang="ts">
import type { LucideIcon} from '@lucide/vue';
import { CircleQuestionMark, Moon, Sunrise, Sunset } from '@lucide/vue'
import DetailSheet from '@/components/dialogs/DetailSheet.vue'
import { Separator } from '@/components/ui/separator'
import type { CalendarTimeSlot, VegetableDaySchedule } from '@/types'

interface Props {
    open: boolean
    dateLabel: string
    schedule: VegetableDaySchedule | null
}

defineProps<Props>()

const emit = defineEmits<{
    'update:open': [value: boolean]
}>()

const TIME_SLOTS: Array<{
    key: CalendarTimeSlot
    label: string
    icon: LucideIcon
}> = [
    { key: 'morning', label: 'Morning (6 AM – 12 PM)', icon: Sunrise },
    { key: 'afternoon', label: 'Afternoon (12 PM – 6 PM)', icon: Sunset },
    { key: 'evening', label: 'Evening (6 PM – 10 PM)', icon: Moon },
    { key: 'unscheduled', label: 'No time slot', icon: CircleQuestionMark },
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
    <DetailSheet
        :open="open"
        :title="dateLabel"
        @update:open="emit('update:open', $event)"
    >
        <div
            v-if="schedule"
            class="flex flex-col gap-6"
        >
            <template
                v-for="slot in TIME_SLOTS"
                :key="slot.key"
            >
                <div v-if="schedule[slot.key]">
                    <!-- Slot header -->
                    <div class="mb-3 flex items-center gap-2">
                        <component
                            :is="slot.icon"
                            class="size-5"
                        />
                        <span class="text-sm font-semibold">{{ slot.label }}</span>
                    </div>

                    <!-- Supply / Demand / Net summary -->
                    <div class="mb-4 flex flex-col gap-1.5 rounded-lg border bg-muted/20 p-3 pl-4 text-xs">
                        <div class="flex items-center justify-between">
                            <span class="flex items-center gap-1.5 text-muted-foreground">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500" />
                                Supply
                            </span>
                            <span class="font-semibold text-emerald-600 tabular-nums dark:text-emerald-400">
                                {{ formatKg(schedule[slot.key]!.supply_kg) }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="flex items-center gap-1.5 text-muted-foreground">
                                <span class="h-1.5 w-1.5 rounded-full bg-amber-500" />
                                Demand
                            </span>
                            <span class="font-semibold text-amber-600 tabular-nums dark:text-amber-400">
                                {{ formatKg(schedule[slot.key]!.demand_kg) }}
                            </span>
                        </div>
                        <Separator class="my-0.5" />
                        <div class="flex items-center justify-between">
                            <span class="font-medium">Net</span>
                            <span
                                class="rounded-full px-2 py-0.5 text-[10px] font-bold tabular-nums"
                                :class="
                                    schedule[slot.key]!.net_kg > 0
                                        ? 'bg-destructive/20 text-destructive dark:bg-destructive/40'
                                        : schedule[slot.key]!.net_kg < 0
                                            ? 'bg-orange-100 text-orange-700'
                                            : 'bg-muted text-muted-foreground'
                                "
                            >
                                {{ formatNetBadge(schedule[slot.key]!.net_kg) }}
                            </span>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <p
            v-else
            class="text-sm text-muted-foreground"
        >
            No schedule data for this day.
        </p>
    </DetailSheet>
</template>
