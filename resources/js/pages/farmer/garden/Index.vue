<script setup lang="ts">
import { ref, computed } from 'vue'
import { Deferred, Head, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import Heading from '@/components/Heading.vue'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { Archive, CalendarClock, Plus, Sprout, Weight, Wheat } from 'lucide-vue-next'
import FarmerOfferingForm from '@/components/farmer/FarmerOfferingForm.vue'
import GardenCard from '@/components/farmer/cards/GardenCard.vue'
import ArchivePlantingDialog from '@/components/features/farmer/dialogs/ArchivePlantingDialog.vue'
import DeletePlantingDialog from '@/components/features/farmer/dialogs/DeletePlantingDialog.vue'
import type { PaginatedResponse } from '@/types/pagination'
import farmer from '@/routes/farmer'
import { archive, destroy, index, store, update } from '@/actions/App/Http/Controllers/Farmer/OfferingController'
import EmptyState from '@/components/EmptyState.vue'
import LargeCard from '@/components/shared/cards/LargeCard.vue'
import { Offering, Summary, VarietyOption } from '@/types/farmer/garden'

interface Props {
  filters: { status: string | null }
  offerings?: PaginatedResponse<Offering>
  varietyOptions?: Record<string, VarietyOption[]>
  summary?: Summary
}

const props = defineProps<Props>()

// Dialog states
const formOpen = ref(false)
const deleteDialogOpen = ref(false)
const archiveDialogOpen = ref(false)

// Active items
const activeOffering = ref<Offering | null>(null)
const offeringToDelete = ref<Offering | null>(null)
const offeringToArchive = ref<Offering | null>(null)

// Form setup using Inertia's useForm
const form = useForm<{
  variety_id: number | null
  image: File | null
  weight_kg: number
  asking_price: number
  expiration_date: string
}>({
  variety_id: null,
  image: null,
  weight_kg: 0,
  asking_price: 0,
  expiration_date: '',
})

// Computed
const activeTab = computed(() => props.filters.status || 'all')

const breadcrumbs = [
  { title: 'Farmer', href: farmer.garden.index().url },
  { title: 'Garden', href: farmer.garden.index().url }
]

// Actions
function handleTabChange(value: string | number) {
  const routeTarget = index({
    query: { status:  value === 'available' ? undefined : value }
  })

  router.visit(routeTarget.url, {
    preserveState: true,
    preserveScroll: true,
    only: ['offerings', 'filters', 'summary']
  })
}

function openCreate() {
  activeOffering.value = null
  form.reset()
  form.clearErrors()
  formOpen.value = true
}

function openEdit(offering: Offering) {
  activeOffering.value = offering
  form.variety_id = offering.variety.id
  form.weight_kg = offering.weight_kg
  form.asking_price = offering.asking_price
  form.expiration_date = offering.expiration_date
  form.image = null
  formOpen.value = true
}

function openArchive(offering: Offering) {
  offeringToArchive.value = offering
  archiveDialogOpen.value = true
}

function openDelete(offering: Offering) {
  offeringToDelete.value = offering
  deleteDialogOpen.value = true
}

function handleSubmit() {
  const routeData = activeOffering.value
    ? update(activeOffering.value.id)
    : store()

    form.transform((data) => ({
      ...data,
      _method: activeOffering.value ? 'PUT' : 'POST'
    })).post(routeData.url,{
      preserveScroll: true,
      onSuccess: () => {
        formOpen.value = false
        form.reset()
      }
    })
}

function handleArchive() {
  if (!offeringToArchive.value) return

  router.post(archive(offeringToArchive.value.id), {}, {
    preserveScroll: true,
    onSuccess: () => {
      archiveDialogOpen.value = false
      offeringToArchive.value = null
    }
  })
}

function handleDelete() {
  if (!offeringToDelete.value) return

  const routeTarget = destroy(offeringToDelete.value.id)

  router.visit(routeTarget.url, {
    method: 'delete',
    preserveScroll: true,
    onSuccess: () => {
      deleteDialogOpen.value = false
      offeringToDelete.value = null
    }
  })
}
</script>

<template>
  <Head title="My Offerings" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-col gap-6 p-4 lg:p-6">
      <!-- Header -->
      <div class="flex items-end justify-between">
        <Heading
          title="My Offerings"
          description="Manage your product offerings for dealers."
        />
        
        <Button @click="openCreate" class="gap-2">
          <Plus class="size-4" />
          New Offering
        </Button>
      </div>

      <!-- Summary Cards -->
       <Deferred data="summary">
        <template #fallback>
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
            <Skeleton v-for="i in 4" :key="i" class="h-24 rounded-lg" />
          </div>
        </template>

        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-2">
          <LargeCard 
              title="Available"
              :value="summary?.total_available"
              subtext="all available offers"
              :icon="Wheat"
              card-class="from-emerald-50 to-green-50 dark:from-emerald-950/20 dark:to-green-950/20"
          />

          <LargeCard 
              title="Archived"
              :value="summary?.total_archived"
              subtext="all archived offers"
              :icon="Archive"
              card-class="from-zinc-100 to-zinc-100 dark:from-zinc-950/20 dark:to-zinc-950/20"
          />

          <LargeCard 
              title="Expiring"
              :value="summary?.expiring_this_week"
              subtext="expiring this week"
              :icon="CalendarClock"
              card-class="from-amber-50 to-orange-50 dark:from-emerald-950/20 dark:to-green-950/20"
          />

          <LargeCard 
              title="Total Value"
              :value="summary?.total_value"
              subtext="all available offers"
              :icon="Weight"
              card-class="from-emerald-50 to-green-50 dark:from-emerald-950/20 dark:to-green-950/20"
          />
      </div>
       </Deferred>

      <!-- Status Tabs -->
      <Tabs :model-value="activeTab" @update:model-value="handleTabChange">
        <TabsList>
          <TabsTrigger value="available">Available</TabsTrigger>
          <TabsTrigger value="archived">Archived</TabsTrigger>
        </TabsList>
      </Tabs>

      <!-- Offerings Grid -->
      <Deferred data="offerings">
        <template #fallback>
          <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            <Skeleton v-for="i in 8" :key="i" class="h-96 rounded-lg" />
          </div>
        </template>

        <EmptyState 
          v-if="offerings?.data.length === 0"
          title="No Posted Offerings Yet."
          description="Post an offering"
          :icon="Sprout"
          button="Add Offering"
        />

        <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
          <GardenCard
            v-for="offering in offerings!.data"
            :key="offering.id"
            :offering="offering"
            @edit="openEdit"
            @archive="openArchive"
            @delete="openDelete"
          />
        </div>
      </Deferred>
    </div>
  </AppLayout>

  <!-- Offering Form Dialog -->
  <FarmerOfferingForm
    :open="formOpen"
    :offering="activeOffering"
    :variety-options="varietyOptions!"
    :form="form"
    @update:open="formOpen = $event"
    @submit="handleSubmit"
  />

  <!-- Archive Confirmation -->
  <ArchivePlantingDialog
    :open="archiveDialogOpen"
    :planting="offeringToArchive"
    @update:open="archiveDialogOpen = $event"
    @confirm="handleArchive"
  />

  <!-- Delete Confirmation -->
  <DeletePlantingDialog
    :open="deleteDialogOpen"
    :planting="offeringToDelete"
    @update:open="deleteDialogOpen = $event"
    @confirm="handleDelete"
  />
</template>
