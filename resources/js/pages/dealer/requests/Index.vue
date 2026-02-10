<script setup lang="ts">
import { ref, computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from '@/components/ui/alert-dialog'
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { Plus, Edit, Check, Trash2, Calendar } from 'lucide-vue-next'
import DealerRequestForm from '@/components/dealer/DealerRequestForm.vue'
import { toast } from 'vue-sonner'
import dealer from '@/routes/dealer'
import type { DealerRequest, PaginatedResponse, VarietyOption } from '@/types/announcement'
import AppLayout from '@/layouts/AppLayout.vue'
import Heading from '@/components/Heading.vue'

interface Summary {
  total_open: number
  total_fulfilled: number
  total_expired: number
  upcoming_transactions: number
}

interface Props {
  filters: {
    status: string | null
  }
  requests?: PaginatedResponse<DealerRequest>
  summary?: Summary
  varietyOptions?: Record<string, VarietyOption[]>
}

const props = defineProps<Props>()

const formOpen = ref(false)
const activeRequest = ref<DealerRequest | null>(null)
const isSubmitting = ref(false)

const deleteDialogOpen = ref(false)
const fulfillDialogOpen = ref(false)
const requestToDelete = ref<DealerRequest | null>(null)
const requestToFulfill = ref<DealerRequest | null>(null)

const activeTab = computed(() => props.filters.status || 'all')
const isLoadingRequests = computed(() => !props.requests)
const isLoadingSummary = computed(() => !props.summary)
const isLoadingOptions = computed(() => !props.varietyOptions)

function handleTabChange(value: string | number) {
  router.visit(dealer.requests.index().url, {
    data: { status: value === 'all' ? undefined : value },
    preserveState: true,
    only: ['requests', 'filters']
  })
}

function openCreate() {
  activeRequest.value = null
  formOpen.value = true
}

function openEdit(request: DealerRequest) {
  activeRequest.value = request
  formOpen.value = true
}

function openFulfill(request: DealerRequest) {
  requestToFulfill.value = request
  fulfillDialogOpen.value = true
}

function openDelete(request: DealerRequest) {
  requestToDelete.value = request
  deleteDialogOpen.value = true
}

function handleSubmit(formData: FormData) {
  isSubmitting.value = true

  if (activeRequest.value) {
    formData.append('_method', 'PUT')
    
    router.post(dealer.requests.update(activeRequest.value.id).url, formData, {
      onSuccess() {
        formOpen.value = false
        isSubmitting.value = false
        toast.success('Request updated successfully!')
      },
      onError() {
        isSubmitting.value = false
      }
    })
  } else {
    router.post(dealer.requests.store().url, formData, {
      onSuccess() {
        formOpen.value = false
        isSubmitting.value = false
        toast.success('Request posted successfully!')
      },
      onError() {
        isSubmitting.value = false
      }
    })
  }
}

function handleFulfill() {
  if (!requestToFulfill.value) return

  router.post(dealer.requests.fulfill(requestToFulfill.value.id).url, {}, {
    onSuccess() {
      fulfillDialogOpen.value = false
      requestToFulfill.value = null
      toast.success('Request marked as fulfilled!')
    }
  })
}

function handleDelete() {
  if (!requestToDelete.value) return

  router.delete(dealer.requests.destroy(requestToDelete.value.id).url, {
    onSuccess() {
      deleteDialogOpen.value = false
      requestToDelete.value = null
      toast.success('Request deleted.')
    }
  })
}

function getStatusBadge(status: string) {
  const badgeMap: Record<string, { class: string; label: string }> = {
    open: { 
      class: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400', 
      label: 'Open' 
    },
    fulfilled: { 
      class: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400', 
      label: 'Fulfilled' 
    },
    expired: { 
      class: 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400', 
      label: 'Expired' 
    }
  };

  return badgeMap[status] ?? { 
    class: 'bg-slate-100 text-slate-800', 
    label: status
  };
}

const breadcrumbs = [
  { title: 'Dealer', href: dealer.requests.index().url },
  { title: 'My Requests', href: dealer.requests.index().url }
]
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
      <div v-if="!isLoadingSummary" class="grid grid-cols-1 gap-4 sm:grid-cols-4">
        <div class="rounded-lg border bg-card p-4">
          <p class="text-sm text-muted-foreground">Open Requests</p>
          <p class="text-2xl font-bold">{{ summary!.total_open }}</p>
        </div>
        <div class="rounded-lg border bg-card p-4">
          <p class="text-sm text-muted-foreground">Fulfilled</p>
          <p class="text-2xl font-bold text-blue-600 dark:text-blue-500">
            {{ summary!.total_fulfilled }}
          </p>
        </div>
        <div class="rounded-lg border bg-card p-4">
          <p class="text-sm text-muted-foreground">Expired</p>
          <p class="text-2xl font-bold">{{ summary!.total_expired }}</p>
        </div>
        <div class="rounded-lg border bg-card p-4">
          <p class="text-sm text-muted-foreground">Upcoming</p>
          <p class="text-2xl font-bold text-orange-600 dark:text-orange-500">
            {{ summary!.upcoming_transactions }}
          </p>
        </div>
      </div>
      <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-4">
        <Skeleton v-for="i in 4" :key="i" class="h-24 rounded-lg" />
      </div>

      <!-- Status Tabs -->
      <Tabs :model-value="activeTab" @update:model-value="handleTabChange">
        <TabsList>
          <TabsTrigger value="all">All</TabsTrigger>
          <TabsTrigger value="open">Open</TabsTrigger>
          <TabsTrigger value="fulfilled">Fulfilled</TabsTrigger>
          <TabsTrigger value="expired">Expired</TabsTrigger>
        </TabsList>
      </Tabs>

      <!-- Requests Table -->
      <div v-if="!isLoadingRequests" class="rounded-lg border">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Transaction Date</TableHead>
              <TableHead>Items</TableHead>
              <TableHead>Total Quantity</TableHead>
              <TableHead>Status</TableHead>
              <TableHead class="text-right">Actions</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-for="request in requests!.data" :key="request.id">
              <TableCell>
                <div class="flex items-center gap-2">
                  <Calendar class="size-4 text-muted-foreground" />
                  <span class="font-medium">{{ request.transaction_date }}</span>
                </div>
              </TableCell>
              <TableCell>
                <div class="max-w-xs space-y-1">
                  <div
                    v-for="item in request.items.slice(0, 3)"
                    :key="item.variety.id"
                    class="text-sm"
                  >
                    {{ item.variety.name }}
                    <span class="text-muted-foreground">
                      ({{ item.quantity_kg }}kg @ ₱{{ item.price_offered }})
                    </span>
                  </div>
                  <div
                    v-if="request.items.length > 3"
                    class="text-xs text-muted-foreground"
                  >
                    +{{ request.items.length - 3 }} more varieties
                  </div>
                </div>
              </TableCell>
              <TableCell>
                <span class="font-medium">{{ request.total_quantity }}kg</span>
              </TableCell>
              <TableCell>
                <span
                  :class="getStatusBadge(request.status).class"
                  class="inline-flex rounded-full px-2 py-1 text-xs font-medium"
                >
                  {{ getStatusBadge(request.status).label }}
                </span>
              </TableCell>
              <TableCell class="text-right">
                <div class="flex justify-end gap-2">
                  <Button
                    v-if="request.status === 'open'"
                    variant="outline"
                    size="sm"
                    @click="openEdit(request)"
                  >
                    <Edit class="size-4" />
                  </Button>
                  <Button
                    v-if="request.status === 'open'"
                    variant="outline"
                    size="sm"
                    @click="openFulfill(request)"
                    class="gap-2"
                  >
                    <Check class="size-4" />
                    Fulfill
                  </Button>
                  <Button
                    variant="outline"
                    size="sm"
                    @click="openDelete(request)"
                  >
                    <Trash2 class="size-4" />
                  </Button>
                </div>
              </TableCell>
            </TableRow>

            <TableRow v-if="requests!.data.length === 0">
              <TableCell colspan="5" class="text-center text-muted-foreground">
                No requests found. Create your first request!
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </div>
      <Skeleton v-else class="h-96 rounded-lg" />
    </div>
  </AppLayout>

  <!-- Request Form Dialog -->
  <DealerRequestForm
    v-if="!isLoadingOptions"
    :open="formOpen"
    :request="activeRequest"
    :variety-options="varietyOptions!"
    :is-submitting="isSubmitting"
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
