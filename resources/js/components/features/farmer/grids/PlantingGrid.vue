<script setup lang="ts">
import { ref, computed } from 'vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { ChevronLeft, ChevronRight, Search } from 'lucide-vue-next'
import type { PaginatedPlantings, Planting } from '@/types/farmer/garden'
import PlantingCard from '@/components/farmer/cards/PlantingCard.vue'

const props = defineProps<{
    plantings: PaginatedPlantings
    searchQuery?: string
}>()

const emit = defineEmits<{
    'open-create': []
    'open-edit': [planting: Planting]
    'open-archive': [planting: Planting]
    'open-delete': [planting: Planting]
    'page-change': [page: number]
    'search': [query: string]
}>()

const localSearchQuery = ref(props.searchQuery ?? '')
const plantingsData = computed(() => props.plantings?.data ?? [])
const hasPrevPage = computed(() => props.plantings && props.plantings.current_page > 1)
const hasNextPage = computed(() => props.plantings && props.plantings.current_page < props.plantings.last_page)

const paginationRange = computed(() => {
    if (!props.plantings) {
        return { start: 0, end: 0 }
    }
    
    return {
        start: (props.plantings.current_page - 1) * props.plantings.per_page + 1,
        end: Math.min(props.plantings.current_page * props.plantings.per_page, props.plantings.total),
    }
})

let searchTimeout: ReturnType<typeof setTimeout> | null = null

function handleSearchInput(event: Event) {
    const query = (event.target as HTMLInputElement).value
    localSearchQuery.value = query

    if (searchTimeout) clearTimeout(searchTimeout)

    searchTimeout = setTimeout(() => {
        emit('search', query)
    }, 300)
}
</script>

<template>
    <div class="flex flex-col gap-6">
        <!-- Search Bar -->
        <div class="flex items-center gap-3">
            <div class="relative flex-1 max-w-sm">
                <Search class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                <Input
                    :value="localSearchQuery"
                    placeholder="Search plantings..."
                    class="pl-10"
                    @input="handleSearchInput"
                />
            </div>
        </div>

        <!-- Plantings Grid -->
        <div 
            v-if="plantingsData.length > 0"
            class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
        >
            <PlantingCard
                v-for="planting in plantingsData"
                :key="planting.id"
                :planting="planting"
                @open-edit="$emit('open-edit', $event)"
                @open-archive="$emit('open-archive', $event)"
                @open-delete="$emit('open-delete', $event)"
            />
        </div>

        <!-- Empty State -->
        <div 
            v-else 
            class="flex flex-col items-center justify-center rounded-lg border-2 border-dashed bg-muted/20 py-16"
        >
            <div class="flex size-16 items-center justify-center rounded-full bg-muted">
                <Search class="size-8 text-muted-foreground" />
            </div>
            <h3 class="mt-4 text-lg font-semibold">No plantings found</h3>
            <p class="mt-2 text-sm text-muted-foreground">
                Try adjusting your search or filter criteria
            </p>
        </div>

        <!-- Pagination -->
        <div 
            v-if="plantings && plantings.total > 0"
            class="flex items-center justify-between rounded-lg border bg-card p-4"
        >
            <span class="text-sm text-muted-foreground">
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

                <span class="px-3 text-sm font-medium">
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
