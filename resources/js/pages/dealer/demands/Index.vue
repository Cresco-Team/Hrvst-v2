<script setup lang="ts">
import { Deferred, Head, router } from '@inertiajs/vue3'
import {
  CalendarClock,
  CalendarX,
  Package,
  PackageCheck,
  PackageSearch,
  Plus,
} from 'lucide-vue-next'
import { computed, ref } from 'vue'
import ConfirmationDialog from '@/components/ConfirmationDialog.vue'
import DemandForm from '@/components/dealer/DemandForm.vue'
import EmptyState from '@/components/EmptyState.vue'
import Heading from '@/components/Heading.vue'
import LargeCard from '@/components/shared/cards/LargeCard.vue'
import MyPostCard from '@/components/shared/cards/MyPostCard.vue'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs'
import AppLayout from '@/layouts/AppLayout.vue'
import dealer from '@/routes/dealer'
import { archive, destroy, fulfill, index } from '@/routes/dealer/demands'
import type { Post, DemandSummary, VarietyOption } from '@/types/marketplace'
import type { PaginatedResponse } from '@/types/pagination'

interface Props {
  summary?: DemandSummary
  filters: { status: string | null }
  demands?: PaginatedResponse<Post>
  varietyOptions?: Record<string, VarietyOption[]>
}

const props = defineProps<Props>()

/* Dialog states */
const formOpen = ref(false)
const deleteDialogOpen = ref(false)
const archiveDialogOpen = ref(false)
const fulfillDialogOpen = ref(false)

/* Active items */
const activeDemand = ref<Post | null>(null)
const demandToArchive = ref<Post | null>(null)
const demandToFulfill = ref<Post | null>(null)
const demandToDelete = ref<Post | null>(null)

const activeTab = computed(() => props.filters.status || 'Ongoing')

const breadcrumbs = [
  { title: 'Dealer', href: dealer.demands.index().url },
  { title: 'Demands', href: dealer.demands.index().url },
]

function handleTabChange(value: string | number) {
  const routeTarget = index({
    query: { status: value === 'open' ? undefined : value },
  })

  router.visit(routeTarget.url, {
    preserveState: true,
    preserveScroll: true,
    only: ['demands', 'filters', 'summary'],
  })
}

function openCreate() {
  activeDemand.value = null
  formOpen.value = true
}

function openEdit(demand: Post) {
  activeDemand.value = demand
  formOpen.value = true
}

function openToArchive(demand: Post) {
  demandToArchive.value = demand
  archiveDialogOpen.value = true
}

function openFulfill(demand: Post) {
  demandToFulfill.value = demand
  fulfillDialogOpen.value = true
}

function openDelete(demand: Post) {
  demandToDelete.value = demand
  deleteDialogOpen.value = true
}

function handleArchive() {
  if (!demandToArchive.value) return

  router.post(
    archive(demandToArchive.value.id),
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
    fulfill(demandToFulfill.value.id),
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

  const routeTarget = destroy(demandToDelete.value.id)

  router.visit(routeTarget.url, {
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
    data: {
      page,
      search: props.filters.status || undefined,
    },
    preserveScroll: true,
  })
}
</script>

<template>

  <Head title="My Demands" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-col gap-6 p-4 lg:p-6">
      <!-- Header -->
      <div class="flex items-end justify-between">
        <Heading title="My Demands" description="Manage your vegetable demands." />

        <Button @click="openCreate" class="gap-2">
          <Plus class="size-4" />
          New Demand
        </Button>
      </div>

      <!-- Summary Cards -->
      <Deferred data="summary">
        <template #fallback>
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
            <Skeleton v-for="i in 4" :key="i" class="h-34 rounded-lg" />
          </div>
        </template>

        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-2">
          <LargeCard title="Ongoing Demands" :value="summary?.total_ongoing" subtext="all ongoing demands"
            :icon="PackageSearch"
            card-class="from-emerald-50 to-green-50 dark:from-emerald-950/20 dark:to-green-950/20" />

          <LargeCard title="Archived Demands" :value="summary?.total_archived" subtext="all archived demands"
            :icon="PackageCheck"
            card-class="from-emerald-50 to-green-50 dark:from-emerald-950/20 dark:to-green-950/20" />

          <LargeCard title="Fulfilled Demands" :value="summary?.total_fulfilled" subtext="all fulfilled demands"
            :icon="CalendarX" card-class="from-emerald-50 to-green-50 dark:from-emerald-950/20 dark:to-green-950/20" />

          <LargeCard title="Upcoming" :value="summary?.scheduled_this_week" subtext="scheduled this week"
            :icon="CalendarClock"
            card-class="from-emerald-50 to-green-50 dark:from-emerald-950/20 dark:to-green-950/20" />
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

        <EmptyState v-if="demands?.data.length === 0" title="No Posted Demands Yet." description="Post a demand"
          :icon="Package" />

        <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <MyPostCard v-for="demand in demands!.data" :key="demand.id" :post="demand" @edit="openEdit"
            @archive="openToArchive" @fulfill="openFulfill" @delete="openDelete" />
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

  <!-- Demand Form Dialog -->
  <DemandForm :open="formOpen" :demand="activeDemand" :variety-options="varietyOptions!"
    @update:open="formOpen = $event" />

  <ConfirmationDialog v-model:open="archiveDialogOpen" title="Archive Demand"
    :description="`Are you sure you want to archvie ${demandToArchive?.variety.vegetable} ${demandToArchive?.variety.name}?`"
    @action="handleArchive" />

  <!-- Fulfill Confirmation -->
  <ConfirmationDialog v-model:open="fulfillDialogOpen" title="Mark as Fulfilled?"
    description="This will mark the demand as fulfilled. You won't be able to edit it after this."
    @action="handleFulfill" />

  <!-- Delete Confirmation -->
  <ConfirmationDialog v-model:open="deleteDialogOpen" title="Delete Demand?"
    description="This action cannot be undone. The post will be permanently deleted." @action="handleDelete"
    variant="destructive" />
</template>
