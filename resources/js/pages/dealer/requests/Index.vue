<script setup lang="ts">
import { ref, computed } from 'vue'
import { Deferred, Head, router, useForm } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import { AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle } from '@/components/ui/alert-dialog'
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { Plus, PackageSearch, PackageCheck, CalendarX, CalendarClock, Package } from 'lucide-vue-next'
import DealerRequestForm from '@/components/dealer/DealerRequestForm.vue'
import dealer from '@/routes/dealer'
import AppLayout from '@/layouts/AppLayout.vue'
import Heading from '@/components/Heading.vue'
import { PaginatedResponse } from '@/types/pagination'
import { Request, Summary, VarietyOption } from '@/types/dealer/requests'
import { destroy, fulfill, index, store, update } from '@/routes/dealer/requests'
import LargeCard from '@/components/shared/cards/LargeCard.vue'
import EmptyState from '@/components/EmptyState.vue'
import RequestCard from '@/components/dealer/RequestCard.vue'

interface Props {
  summary?: Summary
  filters: { status: string | null }
  requests?: PaginatedResponse<Request>
  varietyOptions?: Record<string, VarietyOption[]>
}
const props = defineProps<Props>()

/* Dialog states */
const formOpen = ref(false)
const deleteDialogOpen = ref(false)
const fulfillDialogOpen = ref(false)

/* Active items */
const activeRequest = ref<Request | null>(null)
const requestToDelete = ref<Request | null>(null)
const requestToFulfill = ref<Request | null>(null)

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
  { title: 'Dealer', href: dealer.requests.index().url },
  { title: 'My Requests', href: dealer.requests.index().url }
]

function handleTabChange(value: string | number) {
  const routeTarget = index({
    query: { status: value === 'open' ? undefined : value }
  })

  router.visit(routeTarget.url, {
    preserveState: true,
    preserveScroll: true,
    only: ['requests', 'filters', 'summary']
  })
}

function openCreate() {
  activeRequest.value = null
  form.reset()
  form.clearErrors()
  formOpen.value = true
}

function openEdit(request: Request) {
  activeRequest.value = request
  form.variety_id = request.variety.id
  form.quantity_kg = request.quantity_kg
  form.price_offered = request.price_offered
  form.transaction_date = request.transaction_date
  formOpen.value = true
}

function openFulfill(request: Request) {
  requestToFulfill.value = request
  fulfillDialogOpen.value = true
}

function openDelete(request: Request) {
  requestToDelete.value = request
  deleteDialogOpen.value = true
}

function handleSubmit() {
  const routeData = activeRequest.value
    ? update(activeRequest.value.id)
    : store()

  form.transform((data) => ({
    ...data,
    _method: activeRequest.value ? 'PUT' : 'POST'
  })).post(routeData.url, {
    preserveScroll: true,
    onSuccess: () => {
      formOpen.value = false
      form.reset()
    }
  })
}

function handleFulfill() {
  if (!requestToFulfill.value) return

  router.post(fulfill(requestToFulfill.value.id), {}, {
    preserveScroll: true,
    onSuccess: () => {
      fulfillDialogOpen.value = false
      requestToFulfill.value = null
    }
  })
}

function handleDelete() {
  if (!requestToDelete.value) return

  const routeTarget = destroy(requestToDelete.value.id)

  router.visit(routeTarget.url, {
    method: 'delete',
    preserveScroll: true,
    onSuccess: () => {
      deleteDialogOpen.value = false
      requestToDelete.value = null
    }
  })
}
</script>

<template>
  <Head title="My Requests" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-col gap-6 p-4 lg:p-6">
      <!-- Header -->
      <div class="flex items-end justify-between">
        <Heading
          title="My Requests"
          description="Manage your purchase requests for farmers."
        />
        
        <Button @click="openCreate" class="gap-2">
          <Plus class="size-4" />
          New Request
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
        </TabsList>
      </Tabs>

      <!-- Requests Grid -->
       <Deferred data="requests">
        <template #fallback>
          <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            <Skeleton v-for="i in 8" :key="i" class="h-96 rounded-lg" />
          </div>
        </template>

        <EmptyState 
          v-if="requests?.data.length === 0"
          title="No Posted Requests Yet."
          description="Post a request"
          :icon="Package"
          button="Add Request"
        />

        <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
          <RequestCard 
            v-for="request in requests!.data"
            :key="request.id"
            :request="request"
            @edit="openEdit"
            @fulfill="openFulfill"
            @delete="openDelete"
          />
        </div>
       </Deferred>
    </div>
  </AppLayout>

  <!-- Request Form Dialog -->
  <DealerRequestForm
    :open="formOpen"
    :request="activeRequest"
    :variety-options="varietyOptions!"
    :form="form"
    @update:open="formOpen = $event"
    @submit="handleSubmit"
  />

  <!-- Fulfill Confirmation -->
  <AlertDialog :open="fulfillDialogOpen" @update:open="fulfillDialogOpen = $event">
    <AlertDialogContent>
      <AlertDialogHeader>
        <AlertDialogTitle>Mark as Fulfilled?</AlertDialogTitle>
        <AlertDialogDescription>
          This will mark the request as fulfilled. You won't be able to edit it after this.
        </AlertDialogDescription>
      </AlertDialogHeader>
      <AlertDialogFooter>
        <AlertDialogCancel>Cancel</AlertDialogCancel>
        <AlertDialogAction @click="handleFulfill">
          Confirm
        </AlertDialogAction>
      </AlertDialogFooter>
    </AlertDialogContent>
  </AlertDialog>

  <!-- Delete Confirmation -->
  <AlertDialog :open="deleteDialogOpen" @update:open="deleteDialogOpen = $event">
    <AlertDialogContent>
      <AlertDialogHeader>
        <AlertDialogTitle>Delete Request?</AlertDialogTitle>
        <AlertDialogDescription>
          This action cannot be undone. The request will be permanently deleted.
        </AlertDialogDescription>
      </AlertDialogHeader>
      <AlertDialogFooter>
        <AlertDialogCancel>Cancel</AlertDialogCancel>
        <AlertDialogAction @click="handleDelete" class="bg-destructive text-destructive-foreground">
          Delete
        </AlertDialogAction>
      </AlertDialogFooter>
    </AlertDialogContent>
  </AlertDialog>
</template>
