<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import { Users, Map, List, Loader2 } from 'lucide-vue-next'
import axios from 'axios'
import { toast } from 'vue-sonner'
import AppShell from '@/components/AppShell.vue'
import AppHeader from '@/components/AppHeader.vue'
import AppContent from '@/components/AppContent.vue'
import Heading from '@/components/Heading.vue'
import FarmerSummaryCard from '@/components/features/admin/cards/FarmerSummaryCard.vue'
import FarmerTable from '@/components/features/admin/tables/FarmerTable.vue'
import FarmerMap from '@/components/features/admin/map/FarmerMap.vue'
import FarmerMapFilters from '@/components/features/admin/map/FarmerMapFilters.vue'
import FarmerMapSidebar from '@/components/features/admin/map/FarmerMapSidebar.vue'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group'
import admin from '@/routes/admin'

/* -- Types -- */
interface Planting {
    id: number
    variety: {
        id: number
        name: string
        category: string
        image_path: string
    }
    weight_kg: string
    date_planted: string
    expected_harvest_date: string
    days_until_harvest: number | null
    status_badge: string
}

interface Farmer {
    id: number
    user: {
        id: number
        name: string
        email: string
        phone_number: string
        user_image: string | null
    }
    location: {
        province: string
        municipality: string
        barangay: string
        coordinates: {
            lat: number
            lng: number
        }
    }
    farm_image: string | null
    active_plantings_count: number
    active_plantings: Planting[]
    joined_at: string
    joined_at_human: string
}

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

interface MarkerData {
    id: number
    position: {
        lat: number
        lng: number
    }
    farmer_name: string
    municipality: string
    active_plantings_count: number
    plantings_summary: Array<{
        vegetable: string
        count: number
        varieties: string[]
    }>
}

interface Municipality {
    id: number
    name: string
    province: string
    label: string
}

interface PlantingOption {
    id: number
    name: string
    category: string
}

interface PlantingsByCategory {
    [category: string]: PlantingOption[]
}

interface FarmerDetails {
    id: number
    user: {
        id: number
        name: string
        email: string
        phone_number: string
        user_image: string | null
    }
    location: {
        province: string
        municipality: string
        barangay: string
        full_address: string
        coordinates: {
            lat: number
            lng: number
        }
    }
    farm_image: string | null
    active_plantings: Array<any>
    statistics: {
        total_active_plantings: number
        total_weight: number
        harvesting_soon: number
    }
    joined_at: string
    joined_at_human: string
}

interface Props {
    view: 'list' | 'map'
    farmers?: PaginatedData
    summary?: Summary
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

/* -- Computed -- */
const isListView = computed(() => currentView.value === 'list')
const isMapView = computed(() => currentView.value === 'map')

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

    <AppShell variant="header">
        <AppHeader :breadcrumbs="breadcrumbs" />
        <AppContent variant="header" class="p-4 lg:p-6">
            <div class="flex flex-col gap-6">
                <!-- Page Header with View Toggle -->
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                            <Users class="size-5" />
                        </div>
                        <Heading
                            title="Farmers"
                            description="Manage approved farmers and their active plantings"
                        />
                    </div>
                    
                    <!-- View Toggle -->
                    <ToggleGroup 
                        :model-value="currentView" 
                        type="single"
                        class="border rounded-lg p-1"
                    >
                        <ToggleGroupItem 
                            value="list" 
                            aria-label="List view"
                            @click="switchView('list')"
                            class="gap-2"
                        >
                            <List class="size-4" />
                            <span class="hidden sm:inline">List</span>
                        </ToggleGroupItem>
                        <ToggleGroupItem 
                            value="map" 
                            aria-label="Map view"
                            @click="switchView('map')"
                            class="gap-2"
                        >
                            <Map class="size-4" />
                            <span class="hidden sm:inline">Map</span>
                        </ToggleGroupItem>
                    </ToggleGroup>
                </div>

                <!-- Summary Cards (always visible) -->
                <FarmerSummaryCard v-if="summary" :summary="summary" />
                <div v-else class="grid grid-cols-1 gap-4 md:grid-cols-4">
                    <Skeleton class="h-24 rounded-lg" />
                    <Skeleton class="h-24 rounded-lg" />
                    <Skeleton class="h-24 rounded-lg" />
                    <Skeleton class="h-24 rounded-lg" />
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
                <div v-if="isMapView" class="space-y-4">
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

                    <!-- Map Container -->
                    <div class="relative h-[calc(100vh-28rem)] w-full overflow-hidden rounded-lg border shadow-sm">
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

                    <!-- Map Legend -->
                    <div class="rounded-lg border bg-card p-4">
                        <p class="mb-2 text-sm font-medium">Map Legend</p>
                        <div class="flex flex-wrap gap-3 text-xs">
                            <div class="flex items-center gap-1.5">
                                <div class="size-3 rounded-full bg-blue-500" />
                                <span>Cluster (multiple farmers)</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <div class="size-3 rounded-full bg-green-500" />
                                <span>Leafy vegetables</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <div class="size-3 rounded-full bg-orange-500" />
                                <span>Root vegetables</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <div class="size-3 rounded-full bg-red-500" />
                                <span>Fruiting vegetables</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <div class="size-3 rounded-full bg-gray-500" />
                                <span>Other varieties</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AppContent>

        <!-- Farmer Details Sidebar (Map View Only) -->
        <FarmerMapSidebar
            v-if="isMapView"
            :open="sidebarOpen"
            :farmer="selectedFarmer"
            :loading="loadingFarmer"
            @close="handleSidebarClose"
        />
    </AppShell>
</template>
