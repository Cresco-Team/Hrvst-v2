<script setup lang="ts">
import { Deferred, Head } from '@inertiajs/vue3'
import axios from 'axios'
import { Loader2, Map } from 'lucide-vue-next'
import { computed, ref, watch } from 'vue'
import { toast } from 'vue-sonner'
import SupplyMap from '@/components/features/map/SupplyMap.vue'
import SupplyMapDialog from '@/components/features/map/SupplyMapDialog.vue'
import SupplyMapFiltersPanel from '@/components/features/map/SupplyMapFilters.vue'
import Heading from '@/components/Heading.vue'
import AppLayout from '@/layouts/AppLayout.vue'
import farmer from '@/routes/farmer'
import type {
  BreadcrumbItem,
  FarmerSupplyMapProps,
  SupplyMapFilters,
  SupplyMarker,
} from '@/types'

defineProps<FarmerSupplyMapProps>()

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Farmer', href: farmer.supplies.index().url },
  { title: 'Supply Map', href: farmer.supplyMap.index().url },
]

/* ── State ───────────────────────────────────────────── */

const markers = ref<SupplyMarker[]>([])
const loading = ref(false)
const selectedMarker = ref<SupplyMarker | null>(null)
const dialogOpen = ref(false)

const filters = ref<SupplyMapFilters>({
  category_id: null,
  variety_id: null,
})

/* ── Computed ────────────────────────────────────────── */

const totalSupplies = computed(() =>
  markers.value.reduce((sum, m) => sum + m.supply_count, 0),
)

/* ── Data Fetching ───────────────────────────────────── */

async function fetchMarkers(): Promise<void> {
  loading.value = true
  try {
    const params: Record<string, number> = {}
    if (filters.value.category_id) params.category_id = filters.value.category_id
    if (filters.value.variety_id) params.variety_id = filters.value.variety_id

    const { data } = await axios.get<{ markers: SupplyMarker[] }>(
      farmer.supplyMap.markers().url,
      { params },
    )
    markers.value = data.markers
  } catch (error: unknown) {
    const message = error instanceof Error ? error.message : 'Please try again.'
    toast.error('Failed to load map data', { description: message })
  } finally {
    loading.value = false
  }
}

/* ── Event Handlers ──────────────────────────────────── */

function handleMarkerClick(marker: SupplyMarker): void {
  selectedMarker.value = marker
  dialogOpen.value = true
}

function handleFilterUpdate(updated: SupplyMapFilters): void {
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

      <div class="flex items-end justify-between">
        <Heading title="Supply Distribution Map"
          description="See where vegetable supplies are active across municipalities and barangays." />
      </div>

      <div class="grid grid-cols-1 gap-4 lg:grid-cols-[1fr_300px]">

        <!-- Map -->
        <div class="relative min-h-[580px] rounded-lg border shadow-sm overflow-hidden">

          <Transition enter-active-class="transition-opacity duration-200"
            leave-active-class="transition-opacity duration-200" enter-from-class="opacity-0"
            leave-to-class="opacity-0">
            <div v-if="loading"
              class="absolute inset-0 z-30 flex items-center justify-center bg-background/80 backdrop-blur-sm">
              <div class="flex items-center gap-2 rounded-lg border bg-card px-4 py-3 shadow-lg">
                <Loader2 class="size-4 animate-spin" />
                <span class="text-sm font-medium">Loading supply data…</span>
              </div>
            </div>
          </Transition>

          <Transition enter-active-class="transition-opacity duration-200"
            leave-active-class="transition-opacity duration-200" enter-from-class="opacity-0"
            leave-to-class="opacity-0">
            <div v-if="!loading && markers.length === 0"
              class="absolute inset-0 z-20 flex items-center justify-center bg-background/60 backdrop-blur-sm pointer-events-none">
              <div class="flex flex-col items-center gap-2 rounded-lg border bg-card px-6 py-5 shadow-lg text-center">
                <Map class="size-8 text-muted-foreground" />
                <p class="text-sm font-medium">No supplies found</p>
                <p class="text-xs text-muted-foreground">Try adjusting your filters.</p>
              </div>
            </div>
          </Transition>

          <SupplyMap :markers="markers" :config="mapConfig" @marker-click="handleMarkerClick" />
        </div>

        <!-- Right panel: filters -->
        <div class="flex flex-col gap-4">
          <Deferred data="filterOptions">
            <template #fallback>
              <SupplyMapFiltersPanel :filters="filters" :options="null" :total-markers="markers.length"
                :total-supplies="totalSupplies" @update:filters="handleFilterUpdate" @clear="handleClearFilters" />
            </template>
            <SupplyMapFiltersPanel :filters="filters" :options="filterOptions ?? null" :total-markers="markers.length"
              :total-supplies="totalSupplies" @update:filters="handleFilterUpdate" @clear="handleClearFilters" />
          </Deferred>
        </div>
      </div>

      <SupplyMapDialog v-model:open="dialogOpen" :marker="selectedMarker" />

    </div>
  </AppLayout>
</template>
