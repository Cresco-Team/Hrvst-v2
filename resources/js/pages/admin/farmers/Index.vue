<script setup lang="ts">

import { Deferred, Head, router } from '@inertiajs/vue3'
import axios from 'axios'
import { Users, Map, List, Loader2, SearchX, UserPlus, PackagePlus, Package } from 'lucide-vue-next'
import { ref, watch, computed } from 'vue'
import { toast } from 'vue-sonner'
import EmptyState from '@/components/EmptyState.vue'
import FarmerMap from '@/components/features/admin/map/FarmerMap.vue'
import FarmerMapFilters from '@/components/features/admin/map/FarmerMapFilters.vue'
import FarmerMapSidebar from '@/components/features/admin/map/FarmerMapSidebar.vue'
import FarmerTable from '@/components/features/admin/tables/FarmerTable.vue'
import Heading from '@/components/Heading.vue'
import LargeCard from '@/components/shared/cards/LargeCard.vue'
import { Skeleton } from '@/components/ui/skeleton'
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group'
import AppLayout from '@/layouts/AppLayout.vue'
import admin from '@/routes/admin'
import type { Filters, Farmer, Summary, FarmerDetails, MarkerData } from '@/types/admin/farmers'
import type { PaginatedResponse } from '@/types/pagination'

interface Props {
    view: 'list' | 'map'
    filters: Filters
    mapConfig: {
        center: {
            lat: number
            lng: number
        }
        defaultZoom: number
    }
    farmers: PaginatedResponse<Farmer>
    summary: Summary
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

const totalVisiblePlantings = computed(() => {
    return markers.value.reduce((sum, m) => sum + m.ongoing_supplies_count, 0)
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

        if (selectedMunicipality.value) params.municipality_id = selectedMunicipality.value

        if (selectedVariety.value) params.variety_id = selectedVariety.value

        if (mapBounds.value) params.bounds = mapBounds.value

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
    fetchFarmerDetails(farmer.id)
}

function handlePageChange(page: number) {
    router.visit(admin.farmers.index().url, {
        data: { page, view: 'list' },
        preserveState: true,
        preserveScroll: true,
    })
}

function openFarmerSidebar(farmerId: number) {
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
                <Heading title="Farmers" description="Manage farmers and their active plantings." />


                    <!-- View Toggle -->
                    <ToggleGroup :model-value="currentView" variant="outline" type="single">
                        <ToggleGroupItem value="list" aria-label="List view" @click="switchView('list')">
                            <List class="size-4" />
                            <span class="hidden sm:inline">List</span>
                        </ToggleGroupItem>
                        <ToggleGroupItem value="map" aria-label="Map view" @click="switchView('map')">
                            <Map class="size-4" />
                            <span class="hidden sm:inline">Map</span>
                        </ToggleGroupItem>
                    </ToggleGroup>
            </div>

            <!-- Summary Cards -->
            <Deferred data="summary">
                <template #fallback>
                    <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-2">
                        <Skeleton v-for="i in 4" :key="i" class="h-33" />
                    </div>
                </template>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-2">
                    <LargeCard title="Total Farmers" :value="summary.total_farmers" subtext="all approved farmers"
                        :icon="Users"
                        card-class="col-span-1 bg-linear-to-br from-orange-500/10 via-amber-500/10 to-yellow-500/30" />
                    <LargeCard title="New Farmers" :value="summary.new_farmers_this_month"
                        subtext="registered farmers this month" :icon="UserPlus"
                        card-class="col-span-1 bg-linear-to-br from-orange-500/10 via-amber-500/10 to-yellow-500/30" />
                    <LargeCard title="Total Supplies" :value="summary.total_supplies" subtext="all supplies posted"
                        :icon="Package"
                        card-class="col-span-1 bg-linear-to-br from-orange-500/10 via-amber-500/10 to-yellow-500/30" />
                    <LargeCard title="New Supplies" :value="summary.new_supplies_this_month" subtext="posted supplies this month"
                        :icon="PackagePlus"
                        card-class="col-span-1 bg-linear-to-br from-orange-500/10 via-amber-500/10 to-yellow-500/30" />
                </div>
            </Deferred>

            <!-- LIST VIEW -->
            <div v-if="isListView">
                <Deferred data="farmers">
                    <template #fallback>
                        <div class="flex flex-col gap-4">
                            <Skeleton class="h-10 w-80" />
                            <div class="rounded-lg border p-4 space-y-3">
                                <Skeleton v-for="i in 5" :key="i" class="h-16 w-full" />
                            </div>
                        </div>
                    </template>

                    <EmptyState 
                        v-if="farmers.data.length === 0"
                        title="No Farmers Yet"
                        description="Please wait for farmers to register or check for pending farmers."
                        :icon="SearchX"
                    />

                    <FarmerTable v-else :farmers="farmers" @view-farmer="handleViewFarmer"
                        @page-change="handlePageChange" />
                </Deferred>
            </div>

            <!-- MAP VIEW -->
            <div v-if="isMapView" class="grid grid-cols-1 gap-4 lg:grid-cols-[1fr_320px]">
                <!-- Map Container -->
                <div class="relative h-full min-h-[600px] w-full overflow-hidden rounded-lg border shadow-sm">
                    <!-- Loading Overlay -->
                    <Transition enter-active-class="transition-opacity duration-200"
                        leave-active-class="transition-opacity duration-200" enter-from-class="opacity-0"
                        leave-to-class="opacity-0">
                        <div v-if="loadingMarkers"
                            class="absolute inset-0 z-30 flex items-center justify-center bg-background/80 backdrop-blur-sm">
                            <div class="flex items-center gap-2 rounded-lg border bg-card p-4 shadow-lg">
                                <Loader2 class="size-4 animate-spin" />
                                <span class="text-sm font-medium">Loading farmers...</span>
                            </div>
                        </div>
                    </Transition>

                    <!-- Map Component -->
                    <FarmerMap :markers="markers" :center="mapConfig.center" :zoom="mapConfig.defaultZoom"
                        @marker-click="openFarmerSidebar" @bounds-change="handleBoundsChange" />
                </div>

                <!-- Right Sidebar: Filters & Legend -->
                <div class="flex flex-col gap-4">
                    <!-- Map Filters -->
                    <FarmerMapFilters :municipalities="filters.municipalities" :plantings="filters.offerings"
                        :selected-municipality="selectedMunicipality" :selected-variety="selectedVariety"
                        @update:selected-municipality="selectedMunicipality = $event"
                        @update:selected-variety="selectedVariety = $event" @clear="handleClearFilters" />

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
    <FarmerMapSidebar :open="sidebarOpen" :farmer="selectedFarmer" :loading="loadingFarmer"
        @close="handleSidebarClose" />
</template>
