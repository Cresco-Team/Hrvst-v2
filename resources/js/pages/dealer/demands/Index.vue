<script setup lang="ts">
import { ref, computed } from 'vue'
import { Deferred, Head, router, useForm } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { Plus, PackageSearch, PackageCheck, CalendarX, CalendarClock, Package } from 'lucide-vue-next'
import DemandForm from '@/components/dealer/DemandForm.vue'
import dealer from '@/routes/dealer'
import AppLayout from '@/layouts/AppLayout.vue'
import Heading from '@/components/Heading.vue'
import { PaginatedResponse } from '@/types/pagination'
import { Demand, Summary, VarietyOption } from '@/types/dealer/demands'
import { destroy, fulfill, index, store, update } from '@/routes/dealer/demands'
import LargeCard from '@/components/shared/cards/LargeCard.vue'
import EmptyState from '@/components/EmptyState.vue'
import DemandCard from '@/components/dealer/DemandCard.vue'
import ConfirmationDialog from '@/components/ConfirmationDialog.vue'

interface Props {
  summary?: Summary
  filters: { status: string | null }
  demands?: PaginatedResponse<Demand>
  varietyOptions?: Record<string, VarietyOption[]>
}

const props = defineProps<Props>()

/* Dialog states */
const formOpen = ref(false)
const deleteDialogOpen = ref(false)
const fulfillDialogOpen = ref(false)

/* Active items */
const activeDemand = ref<Demand | null>(null)
const demandToFulfill = ref<Demand | null>(null)
const demandToDelete = ref<Demand | null>(null)

const form = useForm<{
  variety_id: number | null
  quantity_kg: number
  price_offered: number
  transaction_date: string
}>({
  variety_id: null,
  quantity_kg: 0,
  price_offered: 0,
  transaction_date: '',
})

const activeTab = computed(() => props.filters.status || 'open')

const breadcrumbs = [
  { title: 'Dealer', href: dealer.demands.index().url },
  { title: 'My Posts', href: dealer.demands.index().url }
]

function handleTabChange(value: string | number) {
  const routeTarget = index({
    query: { status: value === 'open' ? undefined : value }
  })

  router.visit(routeTarget.url, {
    preserveState: true,
    preserveScroll: true,
    only: ['demands', 'filters', 'summary']
  })
}

function openCreate() {
  activeDemand.value = null
  form.reset()
  form.clearErrors()
  formOpen.value = true
}

function openEdit(demand: Demand) {
  activeDemand.value = demand
  form.variety_id = demand.variety.id
  form.quantity_kg = demand.quantity_kg
  form.price_offered = demand.price_offered
  form.transaction_date = demand.transaction_date
  formOpen.value = true
}

function openFulfill(demand: Demand) {
  demandToFulfill.value = demand
  fulfillDialogOpen.value = true
}

function openDelete(demand: Demand) {
  demandToDelete.value = demand
  deleteDialogOpen.value = true
}

function handleSubmit() {
  const routeData = activeDemand.value
    ? update(activeDemand.value.id)
    : store()

  form.transform((data) => ({
    ...data,
    _method: activeDemand.value ? 'PUT' : 'POST'
  })).post(routeData.url, {
    preserveScroll: true,
    onSuccess: () => {
      formOpen.value = false
      form.reset()
    }
  })
}

function handleFulfill() {
  if (!demandToFulfill.value) return

  router.post(fulfill(demandToFulfill.value.id), {}, {
    preserveScroll: true,
    onSuccess: () => {
      fulfillDialogOpen.value = false
      demandToFulfill.value = null
    }
  })
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
    }
  })
}
</script>

<template>
  <Head title="My Posts" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-col gap-6 p-4 lg:p-6">
      <!-- Header -->
      <div class="flex items-end justify-between">
        <Heading
          title="My Posts"
          description="Manage your vegetable requests."
        />
        
        <Button @click="openCreate" class="gap-2">
          <Plus class="size-4" />
          New Post
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
          <LargeCard 
            title="Open Requests"
            :value="summary?.total_open"
            subtext="all open requests"
            :icon="PackageSearch"
            card-class="from-emerald-50 to-green-50 dark:from-emerald-950/20 dark:to-green-950/20"
          />

          <LargeCard 
            title="Fulfilled"
            :value="summary?.total_fulfilled"
            subtext="all fulfilled requests"
            :icon="PackageCheck"
            card-class="from-emerald-50 to-green-50 dark:from-emerald-950/20 dark:to-green-950/20"
          />

          <LargeCard 
            title="Expired Requests"
            :value="summary?.total_expired"
            subtext="all expired requests"
            :icon="CalendarX"
            card-class="from-emerald-50 to-green-50 dark:from-emerald-950/20 dark:to-green-950/20"
          />

          <LargeCard 
            title="Upcoming"
            :value="summary?.upcomming_transactions"
            subtext="all upcoming transactions"
            :icon="CalendarClock"
            card-class="from-emerald-50 to-green-50 dark:from-emerald-950/20 dark:to-green-950/20"
          />
        </div>
       </Deferred>

      <!-- Status Tabs -->
      <Tabs :model-value="activeTab" @update:model-value="handleTabChange">
        <TabsList>
          <TabsTrigger value="open">Open</TabsTrigger>
          <TabsTrigger value="expired">Expired</TabsTrigger>
          <TabsTrigger value="fulfilled">Fulfilled</TabsTrigger>
        </TabsList>
      </Tabs>

      <!-- Demands Grid -->
      <Deferred data="demands">
        <template #fallback>
          <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            <Skeleton v-for="i in 8" :key="i" class="h-96 rounded-lg" />
          </div>
        </template>

        <EmptyState 
          v-if="demands?.data.length === 0"
          title="No Posted Demands Yet."
          description="Post a demand"
          :icon="Package"
          button="Create Post"
        />

        <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <DemandCard 
            v-for="demand in demands!.data"
            :key="demand.id"
            :demand="demand"
            @edit="openEdit"
            @fulfill="openFulfill"
            @delete="openDelete"
          />
        </div>
      </Deferred>
    </div>
  </AppLayout>

  <!-- Demand Form Dialog -->
  <DemandForm
    :open="formOpen"
    :demand="activeDemand"
    :variety-options="varietyOptions!"
    :form="form"
    @update:open="formOpen = $event"
    @submit="handleSubmit"
  />

  <!-- Fulfill Confirmation -->
  <ConfirmationDialog
    v-model:open="fulfillDialogOpen"
    title="Mark as Fulfilled?"
    description="This will mark the demand as fulfilled. You won't be able to edit it after this."
    @action="handleFulfill"
  />

  <!-- Delete Confirmation -->
  <ConfirmationDialog
    v-model:open="deleteDialogOpen"
    title="Delete Post?"
    description="This action cannot be undone. The post will be permanently deleted."
    @action="handleDelete"
    variant="destructive"
  />
</template>
