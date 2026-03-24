<script setup lang="ts">
import { Deferred, Head, router } from '@inertiajs/vue3'
import { Archive, PackageCheck, Plus, ShoppingBag, Sprout } from 'lucide-vue-next'
import { computed, ref } from 'vue'
import ConfirmationDialog from '@/components/dialogs/ConfirmationDialog.vue'
import EmptyState from '@/components/EmptyState.vue'
import DemandForm from '@/components/features/dealer/DemandForm.vue'
import Heading from '@/components/Heading.vue'
import LargeCard from '@/components/shared/cards/LargeCard.vue'
import MyPostCard from '@/components/shared/cards/MyPostCard.vue'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs'
import AppLayout from '@/layouts/AppLayout.vue'
import dealer from '@/routes/dealer'
import { archive, destroy, fulfill, index } from '@/routes/dealer/demands'
import type {
  BreadcrumbItem,
  DealerDemandResource,
  DealerDemandsProps,
  DemandVarietyOption,
  FarmerSupplyResource,
  VarietyOptionsByCategory,
} from '@/types'

// Union emitted by MyPostCard — handlers must accept both sides of the union.
// This page only ever passes DealerDemandResource instances so the cast is safe.
type PostItem = FarmerSupplyResource | DealerDemandResource

const props = defineProps<DealerDemandsProps>()

/* --- State --- */
const formOpen = ref(false)
const activeDemand = ref<DealerDemandResource | null>(null)

const archiveDialogOpen = ref(false)
const fulfillDialogOpen = ref(false)
const deleteDialogOpen = ref(false)
const demandToArchive = ref<DealerDemandResource | null>(null)
const demandToFulfill = ref<DealerDemandResource | null>(null)
const demandToDelete = ref<DealerDemandResource | null>(null)

/* --- Computed --- */
const activeTab = computed(() => props.filters.status ?? 'Ongoing')

/* --- Actions --- */
function handleTabChange(value: string | number) {
  router.visit(index({ query: { status: value === 'Ongoing' ? undefined : value } }).url, {
    preserveState: true,
    preserveScroll: true,
    only: ['demands', 'filters', 'summary'],
  })
}

function openCreate() {
  activeDemand.value = null
  formOpen.value = true
}

function openEdit(demand: PostItem) {
  activeDemand.value = demand as DealerDemandResource
  formOpen.value = true
}

function openArchive(demand: PostItem) {
  demandToArchive.value = demand as DealerDemandResource
  archiveDialogOpen.value = true
}

function openFulfill(demand: PostItem) {
  demandToFulfill.value = demand as DealerDemandResource
  fulfillDialogOpen.value = true
}

function openDelete(demand: PostItem) {
  demandToDelete.value = demand as DealerDemandResource
  deleteDialogOpen.value = true
}

function handleArchive() {
  if (!demandToArchive.value) return
  router.post(
    archive(demandToArchive.value.id).url,
    {},
    {
      preserveScroll: true,
      onSuccess: () => {
        archiveDialogOpen.value = false
        demandToArchive.value = null
      },
    },
  )
}

function handleFulfill() {
  if (!demandToFulfill.value) return
  router.post(
    fulfill(demandToFulfill.value.id).url,
    {},
    {
      preserveScroll: true,
      onSuccess: () => {
        fulfillDialogOpen.value = false
        demandToFulfill.value = null
      },
    },
  )
}

function handleDelete() {
  if (!demandToDelete.value) return
  router.visit(destroy(demandToDelete.value.id).url, {
    method: 'delete',
    preserveScroll: true,
    onSuccess: () => {
      deleteDialogOpen.value = false
      demandToDelete.value = null
    },
  })
}

function handlePageChange(page: number) {
  router.visit(dealer.demands.index().url, {
    data: { page, status: props.filters.status },
    preserveScroll: true,
  })
}

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dealer', href: dealer.demands.index().url },
  { title: 'Demands', href: dealer.demands.index().url },
]
</script>

<template>

  <Head title="My Demands" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-col gap-6 p-4 lg:p-6">

      <div class="flex items-end justify-between">
        <Heading title="My Demands" description="Schedule your demands for pick-up." />
        <Button class="gap-2" @click="openCreate">
          <Plus class="size-4" />
          New Demand
        </Button>
      </div>

      <!-- Summary Cards -->
      <Deferred data="summary">
        <template #fallback>
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
            <Skeleton v-for="i in 4" :key="i" class="h-24 rounded-lg" />
          </div>
        </template>

        <div class="grid md:grid-cols-3 gap-4">
          <LargeCard title="Ongoing Demands" :value="summary?.total_ongoing" subtext="not yet picked-up"
            :icon="ShoppingBag" card-class="bg-green-50 dark:bg-green-950/20" />
          <LargeCard title="Archived Demands" :value="summary?.total_archived" subtext="picked-up from trading post"
            :icon="Archive" card-class="bg-green-50 dark:bg-green-950/20" />
          <LargeCard title="Fulfilled Demands" :value="summary?.total_fulfilled" subtext="marked as successful"
            :icon="PackageCheck" card-class="bg-green-50 dark:bg-green-950/20" />

        </div>
      </Deferred>

      <!-- Status Tabs -->
      <Tabs :model-value="activeTab" @update:model-value="handleTabChange">
        <TabsList>
          <TabsTrigger value="Ongoing">Ongoing</TabsTrigger>
          <TabsTrigger value="Archived">Archived</TabsTrigger>
          <TabsTrigger value="Fulfilled">Fulfilled</TabsTrigger>
        </TabsList>
      </Tabs>

      <!-- Demands Grid -->
      <Deferred data="demands">
        <template #fallback>
          <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            <Skeleton v-for="i in 8" :key="i" class="h-96 rounded-lg" />
          </div>
        </template>

        <EmptyState v-if="demands?.data.length === 0" title="No Demands Yet."
          description="Post a demand to be picked-up." :icon="Sprout" button="Add Request" />

        <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
          <MyPostCard v-for="demand in demands!.data" :key="demand.id" :post="demand" @edit="openEdit"
            @archive="openArchive" @fulfill="openFulfill" @delete="openDelete" />
        </div>
      </Deferred>

      <div v-if="demands && demands.meta.last_page > 1" class="flex items-center justify-between border-t pt-4">
        <Button variant="outline" size="sm" :disabled="demands.meta.current_page === 1"
          @click="handlePageChange(demands.meta.current_page - 1)">
          Previous
        </Button>
        <span class="text-sm text-muted-foreground">
          Page {{ demands.meta.current_page }} of {{ demands.meta.last_page }}
        </span>
        <Button variant="outline" size="sm" :disabled="demands.meta.current_page === demands.meta.last_page"
          @click="handlePageChange(demands.meta.current_page + 1)">
          Next
        </Button>
      </div>
    </div>
  </AppLayout>

  <DemandForm :open="formOpen" :demand="activeDemand"
    :variety-options="(varietyOptions as VarietyOptionsByCategory<DemandVarietyOption>)"
    @update:open="formOpen = $event" />

  <ConfirmationDialog v-model:open="archiveDialogOpen" title="Archive Demand"
    :description="`Are you sure you want to archive ${demandToArchive?.variety?.vegetable} ${demandToArchive?.variety?.name}?`"
    variant="destructive" @action="handleArchive" />

  <ConfirmationDialog v-model:open="fulfillDialogOpen" title="Fulfill Demand"
    :description="`Are you sure you want to set ${demandToFulfill?.variety?.vegetable} ${demandToFulfill?.variety?.name} as fulfilled?`"
    @action="handleFulfill" />

  <ConfirmationDialog v-model:open="deleteDialogOpen" title="Delete Demand"
    :description="`Are you sure you want to delete ${demandToDelete?.variety?.vegetable} ${demandToDelete?.variety?.name}?`"
    @action="handleDelete" />
</template>
