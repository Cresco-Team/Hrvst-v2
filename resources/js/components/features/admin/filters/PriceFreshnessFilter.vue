<script setup lang="ts">
import { computed } from 'vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { Filter, Check } from 'lucide-vue-next'

interface PriceStats {
    updated_week: number
    updated_month: number
    stale: number
    no_price: number
}

const props = defineProps<{
    activeFilter: string | null
    priceStats: PriceStats
}>()

const emit = defineEmits<{
    'filter-change': [filter: string | null]
}>()

const filters = computed(() => [
    {
        value: null,
        label: 'All Varieties',
        description: 'Show everything',
        count: null,
        indicator: null,
    },
    {
        value: 'week',
        label: 'This Week',
        description: 'Updated in the last 7 days',
        count: props.priceStats.updated_week,
        indicator: 'green',
    },
    {
        value: 'month',
        label: 'This Month',
        description: 'Updated in the last 30 days',
        count: props.priceStats.updated_month,
        indicator: 'blue',
    },
    {
        value: 'stale',
        label: 'Needs Update',
        description: 'Not updated for over 30 days',
        count: props.priceStats.stale,
        indicator: 'orange',
    },
])

const activeFilterLabel = computed(() => {
    const filter = filters.value.find(f => f.value === props.activeFilter)
    return filter?.label || 'All Varieties'
})

const hasActiveFilter = computed(() => props.activeFilter !== null)
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button 
                variant="outline" 
                class="gap-2"
                :class="{ 'border-primary': hasActiveFilter }"
            >
                <Filter class="size-4" />
                <span>{{ activeFilterLabel }}</span>
                <Badge 
                    v-if="hasActiveFilter" 
                    variant="secondary" 
                    class="ml-1 px-1.5 py-0"
                >
                    1
                </Badge>
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-64">
            <DropdownMenuLabel class="flex items-center gap-2">
                <Filter class="size-4" />
                Filter by Price Freshness
            </DropdownMenuLabel>
            <DropdownMenuSeparator />
            
            <DropdownMenuItem
                v-for="filter in filters"
                :key="filter.value || 'all'"
                @click="emit('filter-change', filter.value)"
                class="flex items-center justify-between cursor-pointer"
            >
                <div class="flex items-center gap-2.5">
                    <!-- Indicator dot -->
                    <div 
                        v-if="filter.indicator"
                        class="size-2 rounded-full"
                        :class="{
                            'bg-green-500': filter.indicator === 'green',
                            'bg-blue-500': filter.indicator === 'blue',
                            'bg-orange-500': filter.indicator === 'orange',
                        }"
                    />
                    <div v-else class="size-2" />
                    
                    <!-- Label and description -->
                    <div class="flex flex-col">
                        <span class="text-sm font-medium">{{ filter.label }}</span>
                        <span class="text-xs text-muted-foreground">{{ filter.description }}</span>
                    </div>
                </div>
                
                <div class="flex items-center gap-2">
                    <!-- Count badge -->
                    <Badge 
                        v-if="filter.count !== null" 
                        variant="secondary" 
                        class="text-xs"
                    >
                        {{ filter.count }}
                    </Badge>
                    
                    <!-- Active indicator -->
                    <Check 
                        v-if="activeFilter === filter.value" 
                        class="size-4 text-primary" 
                    />
                </div>
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
