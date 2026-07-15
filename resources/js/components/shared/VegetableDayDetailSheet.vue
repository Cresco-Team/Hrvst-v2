<script setup lang="ts">
import { computed } from 'vue'
import { CalendarClock, ChevronDown, Package, Phone, ShoppingBag, User } from 'lucide-vue-next'
import DetailSheet from '@/components/dialogs/DetailSheet.vue'
import EmptyState from '@/components/EmptyState.vue'
import { Badge } from '@/components/ui/badge'
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible'
import type { CalendarTimeSlot, VegetableDaySchedule } from '@/types'

const props = defineProps<{
    open: boolean
    dateLabel: string
    schedule: VegetableDaySchedule | null
}>()

defineEmits<{ 'update:open': [value: boolean] }>()

const SLOT_LABELS: Record<CalendarTimeSlot, string> = {
    morning: 'Morning',
    afternoon: 'Afternoon',
    evening: 'Evening',
    unscheduled: 'Unscheduled',
}

const SLOT_ORDER: CalendarTimeSlot[] = ['morning', 'afternoon', 'evening', 'unscheduled']

const orderedSlots = computed(() =>
    SLOT_ORDER.filter((slot) => props.schedule?.[slot]),
)

interface PosterGroup {
    post_id: number
    poster_name: string
    poster_phone: string
    total_kg: number
    status: string
}

function groupByPoster(slot: CalendarTimeSlot, type: 'supply' | 'demand'): PosterGroup[] {
    const items = props.schedule?.[slot]?.items ?? []
    const groups = new Map<number, PosterGroup>()

    for (const item of items) {
        if (item.type !== type) continue

        const existing = groups.get(item.post_id)
        if (existing) {
            existing.total_kg += item.quantity_kg
            continue
        }

        groups.set(item.post_id, {
            post_id: item.post_id,
            poster_name: item.poster_name,
            poster_phone: item.poster_phone,
            total_kg: item.quantity_kg,
            status: item.status,
        })
    }

    return Array.from(groups.values()).sort((a, b) => b.total_kg - a.total_kg)
}

function statusVariant(status: string): 'default' | 'secondary' | 'destructive' {
    if (status === 'fulfilled') return 'default'
    if (status === 'expired') return 'destructive'
    return 'secondary'
}

function netClass(netKg: number): string {
    if (netKg > 0) return 'text-primary'
    if (netKg < 0) return 'text-destructive'
    return 'text-muted-foreground'
}
</script>

<template>
    <DetailSheet
        :open="open"
        :title="dateLabel"
        @update:open="$emit('update:open', $event)"
    >
        <EmptyState
            v-if="!orderedSlots.length"
            title="No activity on this day"
        />

        <div
            v-else
            class="flex flex-col gap-6"
        >
            <div
                v-for="slot in orderedSlots"
                :key="slot"
                class="flex flex-col gap-3"
            >
                <div class="flex items-center justify-between">
                    <h3 class="flex items-center gap-1.5 text-sm font-semibold">
                        <CalendarClock class="size-4 text-muted-foreground" />
                        {{ SLOT_LABELS[slot] }}
                    </h3>
                    <span
                        class="text-xs font-semibold tabular-nums"
                        :class="netClass(schedule?.[slot]?.net_kg ?? 0)"
                    >
                        Net {{ (schedule?.[slot]?.net_kg ?? 0).toLocaleString() }} kg
                    </span>
                </div>

                <!-- Farmers (supply) -->
                <div class="flex flex-col gap-1.5">
                    <p class="flex items-center gap-1.5 text-xs font-medium text-muted-foreground">
                        <Package class="size-3.5" />
                        Farmers Supplying ({{ groupByPoster(slot, 'supply').length }})
                    </p>

                    <p
                        v-if="!groupByPoster(slot, 'supply').length"
                        class="pl-5 text-xs text-muted-foreground"
                    >
                        No supply posted
                    </p>

                    <Collapsible
                        v-for="group in groupByPoster(slot, 'supply')"
                        :key="`supply-${group.post_id}`"
                        class="rounded border"
                    >
                        <CollapsibleTrigger class="group/trigger flex w-full items-center justify-between gap-3 bg-primary/5 p-2.5 text-left transition-colors hover:bg-primary/10">
                            <div class="flex min-w-0 items-center gap-2">
                                <User class="size-3.5 shrink-0 text-primary" />
                                <span class="truncate text-sm font-medium">{{ group.poster_name }}</span>
                            </div>
                            <div class="flex shrink-0 items-center gap-2">
                                <span class="text-xs font-semibold tabular-nums">{{ group.total_kg.toLocaleString() }} kg</span>
                                <ChevronDown class="size-3.5 text-muted-foreground transition-transform duration-200 group-data-[state=open]/trigger:rotate-180" />
                            </div>
                        </CollapsibleTrigger>

                        <CollapsibleContent class="flex items-center justify-between border-t bg-muted/20 p-2.5 text-xs">
                            <div class="flex items-center gap-1.5 text-muted-foreground">
                                <Phone class="size-3" />
                                {{ group.poster_phone }}
                            </div>
                            <Badge
                                :variant="statusVariant(group.status)"
                                class="px-1.5 py-0 text-[10px] capitalize"
                            >
                                {{ group.status }}
                            </Badge>
                        </CollapsibleContent>
                    </Collapsible>
                </div>

                <!-- Dealers (demand) -->
                <div class="flex flex-col gap-1.5">
                    <p class="flex items-center gap-1.5 text-xs font-medium text-muted-foreground">
                        <ShoppingBag class="size-3.5" />
                        Dealers Requesting ({{ groupByPoster(slot, 'demand').length }})
                    </p>

                    <p
                        v-if="!groupByPoster(slot, 'demand').length"
                        class="pl-5 text-xs text-muted-foreground"
                    >
                        No demand posted
                    </p>

                    <Collapsible
                        v-for="group in groupByPoster(slot, 'demand')"
                        :key="`demand-${group.post_id}`"
                        class="rounded border"
                    >
                        <CollapsibleTrigger class="group/trigger flex w-full items-center justify-between gap-3 bg-orange-500/5 p-2.5 text-left transition-colors hover:bg-orange-500/10">
                            <div class="flex min-w-0 items-center gap-2">
                                <User class="size-3.5 shrink-0 text-orange-600" />
                                <span class="truncate text-sm font-medium">{{ group.poster_name }}</span>
                            </div>
                            <div class="flex shrink-0 items-center gap-2">
                                <span class="text-xs font-semibold tabular-nums">{{ group.total_kg.toLocaleString() }} kg</span>
                                <ChevronDown class="size-3.5 text-muted-foreground transition-transform duration-200 group-data-[state=open]/trigger:rotate-180" />
                            </div>
                        </CollapsibleTrigger>

                        <CollapsibleContent class="flex items-center justify-between border-t bg-muted/20 p-2.5 text-xs">
                            <div class="flex items-center gap-1.5 text-muted-foreground">
                                <Phone class="size-3" />
                                {{ group.poster_phone }}
                            </div>
                            <Badge
                                :variant="statusVariant(group.status)"
                                class="px-1.5 py-0 text-[10px] capitalize"
                            >
                                {{ group.status }}
                            </Badge>
                        </CollapsibleContent>
                    </Collapsible>
                </div>
            </div>
        </div>
    </DetailSheet>
</template>
