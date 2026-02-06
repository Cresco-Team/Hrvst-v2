<script setup lang="ts">
import { computed } from 'vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import { ChevronLeft, ChevronRight, Search } from 'lucide-vue-next'

interface ArchivedPlanting {
    id: number
    variety_name: string
    category: string
    weight_kg: number
    date_planted: string
    expected_harvest_date: string
    date_completed: string
    status: 'harvested' | 'expired' | 'cancelled'
}

interface PaginatedData {
    data: ArchivedPlanting[]
    current_page: number
    last_page: number
    per_page: number
    total: number
}

const props = defineProps<{
    plantings: PaginatedData
    searchQuery?: string
}>()

const emit = defineEmits<{
    'page-change': [page: number]
    'search': [query: string]
}>()

const hasPrevPage = computed(() => props.plantings.current_page > 1)
const hasNextPage = computed(() => props.plantings.current_page < props.plantings.last_page)

const paginationRange = computed(() => ({
    start: (props.plantings.current_page - 1) * props.plantings.per_page + 1,
    end: Math.min(props.plantings.current_page * props.plantings.per_page, props.plantings.total),
}))

let searchTimeout: ReturnType<typeof setTimeout> | null = null

function handleSearchInput(event: Event) {
    const query = (event.target as HTMLInputElement).value

    if (searchTimeout) clearTimeout(searchTimeout)

    searchTimeout = setTimeout(() => {
        emit('search', query)
    }, 300)
}

function getStatusVariant(status: string): 'default' | 'secondary' | 'destructive' {
    switch (status) {
        case 'harvested':
            return 'default'
        case 'cancelled':
            return 'secondary'
        case 'expired':
            return 'destructive'
        default:
            return 'secondary'
    }
}

function getStatusLabel(status: string): string {
    switch (status) {
        case 'harvested':
            return 'Harvested'
        case 'cancelled':
            return 'Cancelled'
        case 'expired':
            return 'Expired'
        default:
            return status
    }
}
</script>

<template>
    <div class="flex flex-col gap-4">
        <!-- Search Bar -->
        <div class="flex items-center gap-3">
            <div class="relative flex-1 max-w-sm">
                <Search class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                <Input
                    :value="searchQuery"
                    placeholder="Search archived plantings..."
                    class="pl-10"
                    @input="handleSearchInput"
                />
            </div>
        </div>

        <!-- Table -->
        <div class="rounded-lg border">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b bg-muted/50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">
                                Variety
                            </th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">
                                Category
                            </th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">
                                Weight
                            </th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">
                                Date Planted
                            </th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">
                                Expected Harvest
                            </th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">
                                Date Completed
                            </th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">
                                Status
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="planting in plantings.data"
                            :key="planting.id"
                            class="border-b transition-colors hover:bg-muted/40"
                        >
                            <td class="px-4 py-3 font-medium">
                                {{ planting.variety_name }}
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ planting.category }}
                            </td>
                            <td class="px-4 py-3">
                                {{ planting.weight_kg }} kg
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ planting.date_planted }}
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ planting.expected_harvest_date }}
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ planting.date_completed }}
                            </td>
                            <td class="px-4 py-3">
                                <Badge :variant="getStatusVariant(planting.status)">
                                    {{ getStatusLabel(planting.status) }}
                                </Badge>
                            </td>
                        </tr>

                        <!-- Empty State -->
                        <tr v-if="plantings.data.length === 0">
                            <td colspan="7" class="px-4 py-12 text-center">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <Search class="size-12 text-muted-foreground/50" />
                                    <p class="text-sm font-medium text-muted-foreground">
                                        No archived plantings found
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        Completed plantings will appear here
                                    </p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <div 
            v-if="plantings.total > 0"
            class="flex items-center justify-between text-sm text-muted-foreground"
        >
            <span>
                Showing
                <strong class="font-semibold text-foreground">{{ paginationRange.start }}</strong>
                –
                <strong class="font-semibold text-foreground">{{ paginationRange.end }}</strong>
                of
                <strong class="font-semibold text-foreground">{{ plantings.total }}</strong>
                plantings
            </span>

            <div class="flex items-center gap-2">
                <Button
                    variant="outline"
                    size="icon-sm"
                    :disabled="!hasPrevPage"
                    @click="$emit('page-change', plantings.current_page - 1)"
                >
                    <ChevronLeft class="size-4" />
                </Button>

                <span class="px-3 font-medium text-foreground">
                    Page {{ plantings.current_page }} of {{ plantings.last_page }}
                </span>

                <Button
                    variant="outline"
                    size="icon-sm"
                    :disabled="!hasNextPage"
                    @click="$emit('page-change', plantings.current_page + 1)"
                >
                    <ChevronRight class="size-4" />
                </Button>
            </div>
        </div>
    </div>
</template>
