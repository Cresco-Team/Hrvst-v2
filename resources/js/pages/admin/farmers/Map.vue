<script setup lang="ts">
import { ref, watch } from 'vue'
import { Head } from '@inertiajs/vue3'
import axios from 'axios'
import AppShell from '@/components/AppShell.vue'
import AppHeader from '@/components/AppHeader.vue'
import AppContent from '@/components/AppContent.vue'
import Heading from '@/components/Heading.vue'
import FarmerMap from '@/components/features/admin/map/FarmerMap.vue'
import FarmerMapFilters from '@/components/features/admin/map/FarmerMapFilters.vue'
import FarmerMapSidebar from '@/components/features/admin/map/FarmerMapSidebar.vue'
import { Map as MapIcon } from 'lucide-vue-next'
import { toast } from 'vue-sonner';

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
    filters: {
        municipalities: Municipality[]
        plantings: PlantingsByCategory
    }
    mapCenter: {
        lat: number
        lng: number
    }
    defaultZoom: number
}

const props = defineProps<Props>()

// State
const markers = ref<MarkerData[]>([])
const selectedMunicipality = ref<string | null>(null)
const selectedVariety = ref<string | null>(null)
const loadingMarkers = ref(false)
const sidebarOpen = ref(false)
const selectedFarmer = ref<FarmerDetails | null>(null)
const loadingFarmer = ref(false)
const mapBounds = ref<{ north: number; south: number; east: number; west: number } | null>(null)

const breadcrumbs = [
    { title: 'Admin', href: '/admin/dashboard' },
    { title: 'Farmers', href: '/admin/farmers' },
    { title: 'Map View', href: '/admin/farmers-map' },
]

// Fetch markers with filters
const fetchMarkers = async () => {
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

        const response = await axios.get('/admin/farmers-map/markers', { params })
        markers.value = response.data.markers
    } catch (error: any) {
        toast({
            title: 'Error loading markers',
            description: error.response?.data?.message || 'Failed to load farmer markers',
            variant: 'destructive',
        })
    } finally {
        loadingMarkers.value = false
    }
}

// Fetch farmer details
const fetchFarmerDetails = async (farmerId: number) => {
    loadingFarmer.value = true
    sidebarOpen.value = true
    selectedFarmer.value = null

    try {
        const response = await axios.get(`/admin/farmers-map/${farmerId}`)
        selectedFarmer.value = response.data
    } catch (error: any) {
        toast({
            title: 'Error loading farmer details',
            description: error.response?.data?.error || 'Failed to load farmer information',
            variant: 'destructive',
        })
        sidebarOpen.value = false
    } finally {
        loadingFarmer.value = false
    }
}

// Handle marker click
const handleMarkerClick = (farmerId: number) => {
    fetchFarmerDetails(farmerId)
}

// Handle bounds change
const handleBoundsChange = (bounds: { north: number; south: number; east: number; west: number }) => {
    mapBounds.value = bounds
}

// Handle clear filters
const handleClearFilters = () => {
    selectedMunicipality.value = null
    selectedVariety.value = null
}

// Handle sidebar close
const handleSidebarClose = () => {
    sidebarOpen.value = false
    selectedFarmer.value = null
}

// Watch for filter changes - real-time updates
watch([selectedMunicipality, selectedVariety, mapBounds], () => {
    fetchMarkers()
}, { immediate: true })
</script>

<template>
    <Head title="Farmers Map" />

    <AppShell variant="header">
        <AppHeader :breadcrumbs="breadcrumbs" />

        <AppContent variant="header" class="container mx-auto p-4 md:p-6">
            <div class="mb-6 space-y-6">
                <Heading
                    title="Farmers Map"
                    description="View all approved farmers on the map with their active plantings"
                >
                    <template #icon>
                        <MapIcon class="size-5" />
                    </template>
                </Heading>

                <!-- Filters -->
                <FarmerMapFilters
                    :municipalities="filters.municipalities"
                    :plantings="filters.plantings"
                    :selected-municipality="selectedMunicipality"
                    :selected-variety="selectedVariety"
                    @update:selected-municipality="selectedMunicipality = $event"
                    @update:selected-variety="selectedVariety = $event"
                    @clear="handleClearFilters"
                />
            </div>

            <!-- Map Container - Fixed z-index -->
            <div class="relative h-[calc(100vh-20rem)] w-full overflow-hidden rounded-lg border shadow-sm z-10">
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
                            <div class="size-4 animate-spin rounded-full border-2 border-primary border-t-transparent" />
                            <span class="text-sm font-medium">Loading farmers...</span>
                        </div>
                    </div>
                </Transition>

                <!-- Map -->
                <FarmerMap
                    :markers="markers"
                    :center="mapCenter"
                    :zoom="defaultZoom"
                    @marker-click="handleMarkerClick"
                    @bounds-change="handleBoundsChange"
                />
            </div>

            <!-- Map Legend -->
            <div class="mt-4 rounded-lg border bg-card p-4">
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
        </AppContent>

        <!-- Sidebar - Highest z-index -->
        <FarmerMapSidebar
            :open="sidebarOpen"
            :farmer="selectedFarmer"
            :loading="loadingFarmer"
            @close="handleSidebarClose"
        />
    </AppShell>
</template>
