<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import { Users, Map, List, Loader2, Sprout, Clock, TrendingUp } from 'lucide-vue-next'
import axios from 'axios'
import { toast } from 'vue-sonner'
import Heading from '@/components/Heading.vue'
import FarmerTable from '@/components/features/admin/tables/FarmerTable.vue'
import FarmerMap from '@/components/features/admin/map/FarmerMap.vue'
import FarmerMapFilters from '@/components/features/admin/map/FarmerMapFilters.vue'
import FarmerMapSidebar from '@/components/features/admin/map/FarmerMapSidebar.vue'
import PendingFarmersSheet from '@/components/features/admin/sheets/PendingFarmersSheet.vue'
import { Skeleton } from '@/components/ui/skeleton'
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group'
import admin from '@/routes/admin'
import AppLayout from '@/layouts/AppLayout.vue'
import LargeCard from '@/components/shared/cards/LargeCard.vue'
import { Farmer, FarmerDetails, MarkerData, Municipality, PendingFarmer } from '@/types/users/farmer'

/* -- Types -- */
interface PaginatedData {
    data: Farmer[]
    current_page: number
    last_page: number
    per_page: number
    total: number
}

interface Summary {
    total_farmers: number
    total_active_plantings: number
    harvesting_soon: number
    average_plantings_per_farmer: number
}

interface PlantingOption {
    id: number
    name: string
    category: string
}

interface PlantingsByCategory {
    [category: string]: PlantingOption[]
}

interface Props {
    view: 'list' | 'map'
    farmers?: PaginatedData
    summary: Summary
    pendingFarmers: PendingFarmer[]
    filters: {
        municipalities: Municipality[]
        plantings: PlantingsByCategory
    }
    mapConfig: {
        center: {
            lat: number
            lng: number
        }
        defaultZoom: number
    }
}

const props = defineProps<Props>()

/* -- State -- */
const currentView = ref<'list' | 'map'>(props.view)
const markers = ref<MarkerData[]>([])
const selectedMunicipality = ref<string | null>(null)
const selectedVariety = ref<string | null>(null)
const loadingMarkers = ref(false)
const sidebarOpen = ref(false)
const selectedFarmer = ref<FarmerDetails | null>(null)
const loadingFarmer = ref(false)
const mapBounds = ref<{ north: number; south: number; east: number; west: number } | null>(null)
const pendingSheetOpen = ref(false)

/* -- Computed -- */
const isListView = computed(() => currentView.value === 'list')
const isMapView = computed(() => currentView.value === 'map')

const totalVisiblePlantings = computed(() => {
    return markers.value.reduce((sum, m) => sum + m.active_plantings_count, 0)
})

const breadcrumbs = [
    { title: 'Admin', href: admin.dashboard().url },
    { title: 'Farmers', href: admin.farmers.index().url },
]

/* -- View Toggle -- */
function switchView(newView: 'list' | 'map') {
    if (newView === currentView.value) return
    
    // Persist view preference to localStorage
    localStorage.setItem('farmers_view', newView)
    
    // Update URL with query param
    router.visit(admin.farmers.index().url, {
        data: { view: newView },
        preserveState: true,
        preserveScroll: true,
        only: newView === 'list' ? ['farmers', 'summary'] : [],
        onSuccess: () => {
            currentView.value = newView
        }
    })
}

/* -- Map Data Fetching -- */
async function fetchMarkers() {
    loadingMarkers.value = true
    try {
        const params: any = {}
        
        if (selectedMunicipality.value) {
            params.municipality_id = selectedMunicipality.value
        }
        
        if (selectedVariety.value) {
            params.variety_id = selectedVariety.value
        }
        
        if (mapBounds.value) {
            params.bounds = mapBounds.value
        }

        const response = await axios.get('/admin/farmers/api/markers', { params })
        markers.value = response.data.markers
    } catch (error: any) {
        toast.error('Error loading markers', {
            description: error.response?.data?.message || 'Failed to load farmer markers'
        })
    } finally {
        loadingMarkers.value = false
    }
}

async function fetchFarmerDetails(farmerId: number) {
    loadingFarmer.value = true
    sidebarOpen.value = true
    selectedFarmer.value = null

    try {
        const response = await axios.get(`/admin/farmers/api/${farmerId}/details`)
        selectedFarmer.value = response.data
    } catch (error: any) {
        toast.error('Error loading farmer details', {
            description: error.response?.data?.error || 'Failed to load farmer information'
        })
        sidebarOpen.value = false
    } finally {
        loadingFarmer.value = false
    }
}

/* -- Event Handlers -- */
function handleViewFarmer(farmer: Farmer) {
    router.visit(admin.farmers.show(farmer.id))
}

function handlePageChange(page: number) {
    router.visit(admin.farmers.index().url, {
        data: { page, view: 'list' },
        preserveState: true,
        preserveScroll: true,
    })
}

function handleMarkerClick(farmerId: number) {
    fetchFarmerDetails(farmerId)
}

function handleBoundsChange(bounds: { north: number; south: number; east: number; west: number }) {
    mapBounds.value = bounds
}

function handleClearFilters() {
    selectedMunicipality.value = null
    selectedVariety.value = null
}

function handleSidebarClose() {
    sidebarOpen.value = false
    selectedFarmer.value = null
}

/* -- Watchers -- */
// Fetch markers when switching to map view or filters change
watch([currentView, selectedMunicipality, selectedVariety, mapBounds], () => {
    if (currentView.value === 'map') {
        fetchMarkers()
    }
}, { immediate: true })

// Restore view preference on mount
const storedView = localStorage.getItem('farmers_view') as 'list' | 'map' | null
if (storedView && storedView !== props.view) {
    switchView(storedView)
}
</script>

<template>
    <Head title="Farmers" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 lg:p-6">
            
            <!-- Header -->
            <div class="flex items-end justify-between">

                <!-- Title -->
                <Heading
                    title="Farmers"
                    description="Manage farmers and their active plantings."
                />
                
                <div class="flex items-center gap-2">

                    <!-- Pending Approvals Sheet -->
                    <PendingFarmersSheet
                        v-model:open="pendingSheetOpen"
                        :farmers="pendingFarmers"
                    />

                    <!-- View Toggle -->
                    <ToggleGroup 
                        :model-value="currentView"
                        variant="outline" 
                        type="single"
                    >
                        <ToggleGroupItem 
                            value="list" 
                            aria-label="List view"
                            @click="switchView('list')"
                        >
                            <List class="size-4" />
                            <span class="hidden sm:inline">List</span>
                        </ToggleGroupItem>
                        <ToggleGroupItem 
                            value="map" 
                            aria-label="Map view"
                            @click="switchView('map')"
                        >
                            <Map class="size-4" />
                            <span class="hidden sm:inline">Map</span>
                        </ToggleGroupItem>
                    </ToggleGroup>
                </div>
            </div>

            <!-- Summary Cards -->
             <div v-if="summary" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-2">
                <LargeCard 
                    title="Registered Farmers"
                    :value="summary.total_farmers"
                    subtext="approved farmers"
                    :icon="Users"
                    card-class="col-span-1"
                />
                <LargeCard 
                    title="Active Plantings"
                    :value="summary.total_active_plantings"
                    subtext="currently growing"
                    :icon="Sprout"
                    card-class="col-span-1"
                />
                <LargeCard 
                    title="Harvesting Soon"
                    :value="summary.harvesting_soon"
                    subtext="within this week"
                    :icon="Clock"
                    icon-color="text-orange-500"
                    card-class="col-span-1"
                />
                <LargeCard 
                    title="Average Plantings"
                    :value="summary.average_plantings_per_farmer"
                    subtext="per farmer"
                    :icon="TrendingUp"
                    card-class="col-span-1"
                />
             </div>
             <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-2">
                <Skeleton class="h-24 md:h-28 lg:h-32 rounded-lg col-span-1"/>
                <Skeleton class="h-24 md:h-28 lg:h-32 rounded-lg col-span-1" />
                <Skeleton class="h-24 md:h-28 lg:h-32 rounded-lg col-span-1" />
                <Skeleton class="h-24 md:h-28 lg:h-32 rounded-lg col-span-1" />
            </div>
            
            <!-- LIST VIEW -->
            <div v-if="isListView">
                <FarmerTable
                    v-if="farmers"
                    :farmers="farmers"
                    @view-farmer="handleViewFarmer"
                    @page-change="handlePageChange"
                />
                <div v-else class="flex flex-col gap-4">
                    <div class="flex items-center justify-between">
                        <Skeleton class="h-9 w-64" />
                    </div>
                    <div class="rounded-lg border">
                        <div class="p-4 space-y-3">
                            <Skeleton class="h-16 w-full" />
                            <Skeleton class="h-16 w-full" />
                            <Skeleton class="h-16 w-full" />
                            <Skeleton class="h-16 w-full" />
                            <Skeleton class="h-16 w-full" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- MAP VIEW -->
            <div v-if="isMapView" class="grid grid-cols-1 gap-4 lg:grid-cols-[1fr_320px]">
                <!-- Map Container -->
                <div class="relative h-full min-h-[600px] w-full overflow-hidden rounded-lg border shadow-sm">
                    <!-- Loading Overlay -->
                    <Transition
                        enter-active-class="transition-opacity duration-200"
                        leave-active-class="transition-opacity duration-200"
                        enter-from-class="opacity-0"
                        leave-to-class="opacity-0"
                    >
                        <div
                            v-if="loadingMarkers"
                            class="absolute inset-0 z-30 flex items-center justify-center bg-background/80 backdrop-blur-sm"
                        >
                            <div class="flex items-center gap-2 rounded-lg border bg-card p-4 shadow-lg">
                                <Loader2 class="size-4 animate-spin" />
                                <span class="text-sm font-medium">Loading farmers...</span>
                            </div>
                        </div>
                    </Transition>

                    <!-- Map Component -->
                    <FarmerMap
                        :markers="markers"
                        :center="mapConfig.center"
                        :zoom="mapConfig.defaultZoom"
                        @marker-click="handleMarkerClick"
                        @bounds-change="handleBoundsChange"
                    />
                </div>

                <!-- Right Sidebar: Filters & Legend -->
                <div class="flex flex-col gap-4">
                    <!-- Map Filters -->
                    <FarmerMapFilters
                        :municipalities="filters.municipalities"
                        :plantings="filters.plantings"
                        :selected-municipality="selectedMunicipality"
                        :selected-variety="selectedVariety"
                        @update:selected-municipality="selectedMunicipality = $event"
                        @update:selected-variety="selectedVariety = $event"
                        @clear="handleClearFilters"
                    />

                    <!-- Map Stats -->
                    <div class="rounded-lg border bg-card p-4">
                        <p class="mb-3 text-sm font-semibold">Map Statistics</p>
                        <div class="space-y-2 text-sm">
                            <div class="flex items-center justify-between">
                                <span class="text-muted-foreground">Visible farmers</span>
                                <span class="font-mono font-medium">{{ markers.length }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-muted-foreground">Total active plantings</span>
                                <span class="font-mono font-medium">
                                    {{ totalVisiblePlantings }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Map Legend -->
                    <div class="rounded-lg border bg-card p-4">
                        <p class="mb-3 text-sm font-semibold">Legend</p>
                        <div class="space-y-2 text-xs">
                            <div class="flex items-center gap-2">
                                <div class="size-3 shrink-0 rounded-full bg-blue-500" />
                                <span>Cluster (multiple farmers)</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="size-3 shrink-0 rounded-full bg-green-500" />
                                <span>Leafy vegetables</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="size-3 shrink-0 rounded-full bg-orange-500" />
                                <span>Root vegetables</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="size-3 shrink-0 rounded-full bg-red-500" />
                                <span>Fruiting vegetables</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="size-3 shrink-0 rounded-full bg-gray-500" />
                                <span>Other varieties</span>
                            </div>
                        </div>
                    </div>

                    <!-- Instructions -->
                    <div class="rounded-lg border border-dashed bg-muted/30 p-4">
                        <p class="mb-2 text-xs font-medium">How to use</p>
                        <ul class="space-y-1 text-xs text-muted-foreground">
                            <li>• Click markers to view farmer details</li>
                            <li>• Use filters to narrow results</li>
                            <li>• Zoom in to see individual farmers</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>

    <!-- Farmer Details Sidebar (Map View Only) -->
        <FarmerMapSidebar
            v-if="isMapView"
            :open="sidebarOpen"
            :farmer="selectedFarmer"
            :loading="loadingFarmer"
            @close="handleSidebarClose"
        />
</template>
