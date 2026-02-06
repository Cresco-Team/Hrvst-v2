<script setup lang="ts">
import { ref, computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import { Search, Filter } from 'lucide-vue-next'
import PlantingCard from '@/components/features/dealer/cards/PlantingCard.vue'
import MarketInsightsPanel from '@/components/features/dealer/panels/MarketInsightsPanel.vue'
import CategoryFilterBar from '@/components/features/dealer/filters/CategoryFilterBar.vue'
import { Input } from '@/components/ui/input'
import { Skeleton } from '@/components/ui/skeleton'
import { Button } from '@/components/ui/button'
import dealer from '@/routes/dealer'
import AppLayout from '@/layouts/AppLayout.vue'
import Heading from '@/components/Heading.vue'

interface Category {
    id: number
    name: string
}

interface PlantingCard {
    id: number
    variety: {
        id: number
        name: string
        image_path: string
        category: {
            id: number
            name: string
        }
    }
    farmer: {
        id: number
        name: string
        location: {
            barangay: string
            municipality: string
            province: string
            full: string
        }
    }
    weight_kg: number
    harvest_date: string
    harvest_date_human: string
    days_until_harvest: number | null
    urgency: 'normal' | 'soon' | 'overdue'
}

interface PaginatedPlantings {
    data: PlantingCard[]
    current_page: number
    last_page: number
    per_page: number
    total: number
}

interface TrendingVariety {
    variety_id: number
    name: string
    count: number
    change_percent: number
    trend: 'up' | 'down' | 'neutral'
}

interface SupplyGap {
    category_id: number
    category_name: string
    active_count: number
}

interface ForecastWeek {
    week: string
    date_range: string
    total_weight: number
    [category: string]: string | number
}

interface QuickStats {
    total_active_plantings: number
    harvesting_this_week: number
    new_listings_today: number
}

interface Insights {
    trending: TrendingVariety[]
    supply_gaps: SupplyGap[]
    harvest_forecast: ForecastWeek[]
    stats: QuickStats
}

interface Props {
    filters: {
        search: string | null
        category: number | null
        categories: Category[]
    }
    plantings?: PaginatedPlantings
    insights?: Insights
}

const props = defineProps<Props>()

const searchQuery = ref(props.filters.search || '')
const searchInputDebounce = ref<ReturnType<typeof setTimeout> | null>(null)

const isLoadingPlantings = computed(() => !props.plantings)
const isLoadingInsights = computed(() => !props.insights)

function handleSearch() {
    if (searchInputDebounce.value) {
        clearTimeout(searchInputDebounce.value)
    }

    searchInputDebounce.value = setTimeout(() => {
        router.visit(dealer.market().url, {
            data: {
                search: searchQuery.value || undefined,
                category: props.filters.category || undefined,
            },
            preserveState: true,
            preserveScroll: true,
            only: ['plantings'],
        })
    }, 300)
}

function handleCategoryFilter(categoryId: number | null) {
    router.visit(dealer.market().url, {
        data: {
            search: searchQuery.value || undefined,
            category: categoryId || undefined,
        },
        preserveState: true,
        preserveScroll: true,
        only: ['plantings'],
    })
}

function handlePageChange(page: number) {
    router.visit(dealer.market().url, {
        data: {
            search: searchQuery.value || undefined,
            category: props.filters.category || undefined,
            page,
        },
        preserveState: true,
        preserveScroll: true,
    })
}

const breadcrumbs = [
    { title: 'Market', href: dealer.market().url },
]
</script>

<template>
    <Head title="Market" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-col gap-6 p-4 lg:p-6">
            <!-- Page Header with Search -->
            <div class="mb-6 space-y-4">
                <Heading
                    title="Market"
                    description="Browse active plantings from approved farmers."
                />

                <!-- Search Bar -->
                <div class="relative max-w-md">
                    <Search class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        v-model="searchQuery"
                        type="search"
                        placeholder="Search varieties (e.g., Tomato, Lettuce)..."
                        class="pl-10"
                        @input="handleSearch"
                    />
                </div>

                <!-- Category Filter Bar -->
                <CategoryFilterBar
                    :categories="filters.categories"
                    :active-category="filters.category"
                    @select="handleCategoryFilter"
                />
            </div>

            <!-- Main Grid: Plantings + Insights -->
            <div class="grid gap-6 lg:grid-cols-[1fr_380px]">
                <!-- Left: Plantings Grid -->
                <div class="space-y-6">
                    <!-- Results Header -->
                    <div class="flex items-center justify-between">
                        <p class="text-sm text-muted-foreground">
                            <template v-if="plantings">
                                Showing {{ plantings.data.length }} of {{ plantings.total }} plantings
                            </template>
                            <template v-else>
                                Loading...
                            </template>
                        </p>
                    </div>

                    <!-- Plantings Grid -->
                    <div v-if="!isLoadingPlantings && plantings" class="grid gap-4 sm:grid-cols-2">
                        <PlantingCard
                            v-for="planting in plantings.data"
                            :key="planting.id"
                            :planting="planting"
                        />

                        <!-- Empty State -->
                        <div
                            v-if="plantings.data.length === 0"
                            class="col-span-full flex flex-col items-center justify-center rounded-lg border border-dashed py-16 text-center"
                        >
                            <Filter class="mb-4 size-12 text-muted-foreground/50" />
                            <h3 class="mb-1 font-semibold">No plantings found</h3>
                            <p class="text-sm text-muted-foreground">
                                Try adjusting your search or filters
                            </p>
                        </div>
                    </div>

                    <!-- Loading Skeletons -->
                    <div v-else class="grid gap-4 sm:grid-cols-2">
                        <Skeleton v-for="i in 6" :key="i" class="h-64 rounded-lg" />
                    </div>

                    <!-- Pagination -->
                    <div
                        v-if="plantings && plantings.last_page > 1"
                        class="flex items-center justify-between border-t pt-4"
                    >
                        <Button
                            variant="outline"
                            size="sm"
                            :disabled="plantings.current_page === 1"
                            @click="handlePageChange(plantings.current_page - 1)"
                        >
                            Previous
                        </Button>
                        <span class="text-sm text-muted-foreground">
                            Page {{ plantings.current_page }} of {{ plantings.last_page }}
                        </span>
                        <Button
                            variant="outline"
                            size="sm"
                            :disabled="plantings.current_page === plantings.last_page"
                            @click="handlePageChange(plantings.current_page + 1)"
                        >
                            Next
                        </Button>
                    </div>
                </div>

                <!-- Right: Market Insights -->
                <div class="lg:sticky lg:top-6 lg:h-fit">
                    <MarketInsightsPanel
                        v-if="insights"
                        :insights="insights"
                    />
                    <div v-else class="space-y-4">
                        <Skeleton class="h-64 rounded-lg" />
                        <Skeleton class="h-48 rounded-lg" />
                        <Skeleton class="h-56 rounded-lg" />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
