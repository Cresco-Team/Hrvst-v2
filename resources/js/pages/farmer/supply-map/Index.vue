<script setup lang="ts">

import { Deferred, Head } from '@inertiajs/vue3'
import axios from 'axios'
import { Loader2, Map } from 'lucide-vue-next'
import { computed, ref, watch } from 'vue'
import { toast } from 'vue-sonner'
import Heading from '@/components/Heading.vue'
import AppLayout from '@/layouts/AppLayout.vue'
import farmer from '@/routes/farmer'
import type { BarangayMarker, FilterOptions, MapConfig, MapFilters } from '@/types/supply-map'
import SupplyMap from '@/components/features/map/SupplyMap.vue'
import SupplyMapDialog from '@/components/features/map/SupplyMapDialog.vue'
import SupplyMapFilters from '@/components/features/map/SupplyMapFilters.vue'

interface Props {
  mapConfig: MapConfig
  filterOptions?: FilterOptions
}

defineProps<Props>()

const breadcrumbs = [
  { title: 'Farmer', href: farmer.garden.index().url },
  { title: 'Supply Map', href: farmer.supplyMap.index().url },
]

/* ── State ───────────────────────────────────────────── */

const markers        = ref<BarangayMarker[]>([])
const loading        = ref(false)
const selectedMarker = ref<BarangayMarker | null>(null)
const dialogOpen     = ref(false)

const filters = ref<MapFilters>({
  category_id: null,
  variety_id: null,
})

/* ── Computed ────────────────────────────────────────── */

const totalSupplies = computed(() =>
  markers.value.reduce((sum, m) => sum + m.supply_count, 0)
)

/* ── Data Fetching ───────────────────────────────────── */

async function fetchMarkers(): Promise<void> {
  loading.value = true
  try {
    const params: Record<string, number> = {}
    if (filters.value.category_id) params.category_id = filters.value.category_id
    if (filters.value.variety_id)  params.variety_id  = filters.value.variety_id

    const { data } = await axios.get(farmer.supplyMap.markers().url, { params })
    markers.value = data.markers
  } catch (error: any) {
    toast.error('Failed to load map data', {
      description: error.response?.data?.message ?? 'Please try again.',
    })
  } finally {
    loading.value = false
  }
}

/* ── Event Handlers ──────────────────────────────────── */

function handleMarkerClick(marker: BarangayMarker): void {
  selectedMarker.value = marker
  dialogOpen.value = true
}

function handleFilterUpdate(updated: MapFilters): void {
  filters.value = updated
}

function handleClearFilters(): void {
  filters.value = { category_id: null, variety_id: null }
  selectedMarker.value = null
  dialogOpen.value = false
}

/* ── Watchers ────────────────────────────────────────── */

watch(filters, fetchMarkers, { deep: true, immediate: true })
</script>

<template>
  <Head title="Supply Map" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-col gap-6 p-4 lg:p-6">

      <!-- Header -->
      <div class="flex items-end justify-between">
        <Heading
          title="Supply Distribution Map"
          description="See where vegetable supplies are active across municipalities and barangays."
        />
      </div>

      <!-- Content -->
      <div class="grid grid-cols-1 gap-4 lg:grid-cols-[1fr_300px]">

        <!-- Map -->
        <div class="relative min-h-[580px] rounded-lg border shadow-sm overflow-hidden">

          <!-- Loading overlay -->
          <Transition
            enter-active-class="transition-opacity duration-200"
            leave-active-class="transition-opacity duration-200"
            enter-from-class="opacity-0"
            leave-to-class="opacity-0"
          >
            <div
              v-if="loading"
              class="absolute inset-0 z-30 flex items-center justify-center bg-background/80 backdrop-blur-sm"
            >
              <div class="flex items-center gap-2 rounded-lg border bg-card px-4 py-3 shadow-lg">
                <Loader2 class="size-4 animate-spin" />
                <span class="text-sm font-medium">Loading supply data…</span>
              </div>
            </div>
          </Transition>

          <!-- Empty state overlay -->
          <Transition
            enter-active-class="transition-opacity duration-200"
            leave-active-class="transition-opacity duration-200"
            enter-from-class="opacity-0"
            leave-to-class="opacity-0"
          >
            <div
              v-if="!loading && markers.length === 0"
              class="absolute inset-0 z-20 flex items-center justify-center bg-background/60 backdrop-blur-sm pointer-events-none"
            >
              <div class="flex flex-col items-center gap-2 rounded-lg border bg-card px-6 py-5 shadow-lg text-center">
                <Map class="size-8 text-muted-foreground" />
                <p class="text-sm font-medium">No supplies found</p>
                <p class="text-xs text-muted-foreground">Try adjusting your filters.</p>
              </div>
            </div>
          </Transition>

          <SupplyMap
            :markers="markers"
            :config="mapConfig"
            @marker-click="handleMarkerClick"
          />
        </div>

        <!-- Right panel: filters -->
        <div class="flex flex-col gap-4">
          <Deferred data="filterOptions">
            <template #fallback>
              <SupplyMapFilters
                :filters="filters"
                :options="null"
                :total-markers="markers.length"
                :total-supplies="totalSupplies"
                @update:filters="handleFilterUpdate"
                @clear="handleClearFilters"
              />
            </template>
            <SupplyMapFilters
              :filters="filters"
              :options="filterOptions ?? null"
              :total-markers="markers.length"
              :total-supplies="totalSupplies"
              @update:filters="handleFilterUpdate"
              @clear="handleClearFilters"
            />
          </Deferred>
        </div>
      </div>

      <!-- Supply breakdown dialog -->
      <SupplyMapDialog
        v-model:open="dialogOpen"
        :marker="selectedMarker"
      />

    </div>
  </AppLayout>
</template>
