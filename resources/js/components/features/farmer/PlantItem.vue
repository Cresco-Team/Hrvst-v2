<script setup lang="ts">
import { Button } from '@/components/ui/button'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import {
    Item,
    ItemActions,
    ItemContent,
    ItemDescription,
    ItemMedia,
    ItemTitle,
} from '@/components/ui/item'
import { FarmerSupplyDataFixed } from '@/types'
import {
    Calendar,
    CalendarClock,
    Menu,
    MoreVertical,
    SquarePen,
    Trash,
    Weight,
} from 'lucide-vue-next'
import { computed } from 'vue'

const { plant } = defineProps<{ plant: FarmerSupplyDataFixed }>()

const emit = defineEmits<{
    edit: [post: FarmerSupplyDataFixed]
    harvest: [post: FarmerSupplyDataFixed]
    delete: [post: FarmerSupplyDataFixed]
}>()

const canHarvest = computed(() => {
    const d = new Date()
    d.setMonth(d.getMonth() + 1)
    const nextMonth = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`
    return plant.expected_harvest_month! <= nextMonth
})
</script>

<template>
    <Item variant="outline">
        <ItemMedia variant="image">
            <img
                v-if="plant.vegetable?.image_url"
                :src="plant.vegetable?.image_url"
                :alt="plant.vegetable?.name"
            />
        </ItemMedia>

        <ItemContent>
            <ItemTitle>{{ plant.vegetable?.name }}</ItemTitle>
            <div>
                <ItemDescription class="flex">
                    <Calendar class="mr-2 size-4" />
                    Month: {{ plant.expected_harvest_month }}
                </ItemDescription>

                <ItemDescription class="flex">
                    <Weight class="mr-2 size-4" />
                    Weight: {{ plant.estimated_total_weight }} kg
                </ItemDescription>
            </div>
        </ItemContent>

        <ItemActions>
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <Button
                        variant="ghost"
                        size="icon-sm"
                        clas="absolute top-3 right-3"
                    >
                        <MoreVertical class="size-4" />
                    </Button>
                </DropdownMenuTrigger>

                <DropdownMenuContent align="end">
                    <DropdownMenuItem
                        :disabled="!canHarvest"
                        @click="canHarvest && emit('harvest', plant)"
                    >
                        <CalendarClock class="mr-2 size-4 text-primary" />
                        Schedule as Supply
                    </DropdownMenuItem>

                    <DropdownMenuItem @click="emit('edit', plant)">
                        <SquarePen class="mr-2 size-4" />
                        Edit Plant
                    </DropdownMenuItem>

                    <DropdownMenuItem @click="emit('delete', plant)">
                        <Trash class="mr-2 size-4 text-destructive" />
                        Delete Plant
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </ItemActions>
    </Item>
</template>
