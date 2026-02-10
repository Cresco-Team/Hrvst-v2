<script setup lang="ts">
import { ref, computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs'
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
import { Plus, Edit, Archive, Trash2, Clock } from 'lucide-vue-next'
import FarmerOfferingForm from '@/components/farmer/FarmerOfferingForm.vue'
import { toast } from 'vue-sonner'
import farmer from '@/routes/farmer'
import type { FarmerOffering, PaginatedResponse, VarietyOption } from '@/types/announcement'
import AppLayout from '@/layouts/AppLayout.vue'
import Heading from '@/components/Heading.vue'

interface Summary {
  total_active: number
  total_expired: number
  total_archived: number
  expiring_this_week: number
  total_value: number
}

interface Props {
  filters: {
    status: string | null
  }
  offerings?: PaginatedResponse<FarmerOffering>
  varietyOptions?: Record<string, VarietyOption[]>
}

const props = defineProps<Props>()

const formOpen = ref(false)
const activeOffering = ref<FarmerOffering | null>(null)
const isSubmitting = ref(false)

const deleteDialogOpen = ref(false)
const archiveDialogOpen = ref(false)
const offeringToDelete = ref<FarmerOffering | null>(null)
const offeringToArchive = ref<FarmerOffering | null>(null)

const activeTab = computed(() => props.filters.status || 'all')
const isLoadingOfferings = computed(() => !props.offerings)
const isLoadingOptions = computed(() => !props.varietyOptions)

// Mock summary - replace with deferred prop in production
const summary = ref<Summary>({
  total_active: props.offerings?.data.filter(o => o.status === 'active').length || 0,
  total_expired: props.offerings?.data.filter(o => o.status === 'expired').length || 0,
  total_archived: props.offerings?.data.filter(o => o.status === 'archived').length || 0,
  expiring_this_week: props.offerings?.data.filter(o => o.days_until_expiration !== null && o.days_until_expiration <= 7).length || 0,
  total_value: props.offerings?.data.reduce((sum, o) => sum + (o.quantity_kg * o.price_asking), 0) || 0
})

function handleTabChange(value: string | number) {
  router.visit(farmer.offerings.index().url, {
    data: { status: value === 'all' ? undefined : value },
    preserveState: true,
    only: ['offerings', 'filters']
  })
}

function openCreate() {
  activeOffering.value = null
  formOpen.value = true
}

function openEdit(offering: FarmerOffering) {
  activeOffering.value = offering
  formOpen.value = true
}

function openArchive(offering: FarmerOffering) {
  offeringToArchive.value = offering
  archiveDialogOpen.value = true
}

function openDelete(offering: FarmerOffering) {
  offeringToDelete.value = offering
  deleteDialogOpen.value = true
}

function handleSubmit(formData: FormData) {
  isSubmitting.value = true

  if (activeOffering.value) {
    formData.append('_method', 'PUT')
    
    router.post(farmer.offerings.update(activeOffering.value.id).url, formData, {
      onSuccess() {
        formOpen.value = false
        isSubmitting.value = false
        toast.success('Offering updated successfully!')
      },
      onError() {
        isSubmitting.value = false
      }
    })
  } else {
    router.post(farmer.offerings.store().url, formData, {
      onSuccess() {
        formOpen.value = false
        isSubmitting.value = false
        toast.success('Offering posted successfully!')
      },
      onError() {
        isSubmitting.value = false
      }
    })
  }
}

function handleArchive() {
  if (!offeringToArchive.value) return

  router.post(farmer.offerings.archive(offeringToArchive.value.id).url, {}, {
    onSuccess() {
      archiveDialogOpen.value = false
      offeringToArchive.value = null
      toast.success('Offering archived.')
    }
  })
}

function handleDelete() {
  if (!offeringToDelete.value) return

  router.delete(farmer.offerings.destroy(offeringToDelete.value.id).url, {
    onSuccess() {
      deleteDialogOpen.value = false
      offeringToDelete.value = null
      toast.success('Offering deleted.')
    }
  })
}

function getStatusBadge(status: string) {
  const badgeMap: Record<string, { class: string; label: string }> = {
    active: { 
      class: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400', 
      label: 'Active' 
    },
    expired: { 
      class: 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400', 
      label: 'Expired' 
    },
    archived: { 
      class: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400', 
      label: 'Archived' 
    }
  };

  return badgeMap[status] ?? { 
    class: 'bg-gray-100 text-gray-600', 
    label: 'Unknown' 
  };
}

function getUrgencyClass(days: number | null) {
  if (days === null) return ''
  if (days <= 3) return 'text-red-600 dark:text-red-400'
  if (days <= 7) return 'text-orange-600 dark:text-orange-400'
  return 'text-muted-foreground'
}

const breadcrumbs = [
  { title: 'Farmer', href: farmer.offerings.index().url },
  { title: 'My Offerings', href: farmer.offerings.index().url }
]
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
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-5">
        <div class="rounded-lg border bg-card p-4">
          <p class="text-sm text-muted-foreground">Active</p>
          <p class="text-2xl font-bold">{{ summary.total_active }}</p>
        </div>
        <div class="rounded-lg border bg-card p-4">
          <p class="text-sm text-muted-foreground">Expiring Soon</p>
          <p class="text-2xl font-bold text-orange-600 dark:text-orange-500">
            {{ summary.expiring_this_week }}
          </p>
        </div>
        <div class="rounded-lg border bg-card p-4">
          <p class="text-sm text-muted-foreground">Expired</p>
          <p class="text-2xl font-bold">{{ summary.total_expired }}</p>
        </div>
        <div class="rounded-lg border bg-card p-4">
          <p class="text-sm text-muted-foreground">Archived</p>
          <p class="text-2xl font-bold">{{ summary.total_archived }}</p>
        </div>
        <div class="rounded-lg border bg-card p-4">
          <p class="text-sm text-muted-foreground">Total Value</p>
          <p class="text-2xl font-bold text-green-600 dark:text-green-500">
            ₱{{ summary.total_value.toFixed(2) }}
          </p>
        </div>
      </div>

      <!-- Status Tabs -->
      <Tabs :model-value="activeTab" @update:model-value="handleTabChange">
        <TabsList>
          <TabsTrigger value="all">All</TabsTrigger>
          <TabsTrigger value="active">Active</TabsTrigger>
          <TabsTrigger value="expired">Expired</TabsTrigger>
          <TabsTrigger value="archived">Archived</TabsTrigger>
        </TabsList>
      </Tabs>

      <!-- Offerings Grid -->
      <div v-if="!isLoadingOfferings" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        <div
          v-for="offering in offerings!.data"
          :key="offering.id"
          class="group overflow-hidden rounded-lg border bg-card transition-shadow hover:shadow-lg"
        >
          <!-- Image -->
          <div class="relative aspect-square overflow-hidden bg-muted">
            <img
            `   v-if="offering.image_url"
              :src="offering.image_url"
              :alt="offering.variety.name"
              class="size-full object-cover transition-transform group-hover:scale-105"
            />
            <div class="absolute right-2 top-2">
              <span
                :class="getStatusBadge(offering.status).class"
                class="inline-flex rounded-full px-2 py-1 text-xs font-medium"
              >
                {{ getStatusBadge(offering.status).label }}
              </span>
            </div>
          </div>

          <!-- Content -->
          <div class="space-y-3 p-4">
            <div>
              <h3 class="font-semibold">{{ offering.variety.name }}</h3>
              <p class="text-xs text-muted-foreground">{{ offering.variety.category }}</p>
            </div>

            <div class="flex items-center justify-between text-sm">
              <span class="text-muted-foreground">Quantity:</span>
              <span class="font-medium">{{ offering.quantity_kg }}kg</span>
            </div>

            <div class="flex items-center justify-between text-sm">
              <span class="text-muted-foreground">Price:</span>
              <span class="font-medium text-green-600 dark:text-green-500">
                ₱{{ offering.price_asking }}/kg
              </span>
            </div>

            <div class="flex items-center justify-between text-sm">
              <span class="text-muted-foreground">Expires:</span>
              <span :class="getUrgencyClass(offering.days_until_expiration)">
                {{ offering.expiration_date }}
              </span>
            </div>

            <div
              v-if="offering.days_until_expiration !== null"
              class="flex items-center gap-2 text-xs"
              :class="getUrgencyClass(offering.days_until_expiration)"
            >
              <Clock class="size-3" />
              <span v-if="offering.days_until_expiration > 0">
                {{ offering.days_until_expiration }} days left
              </span>
              <span v-else class="font-medium">
                Expired
              </span>
            </div>

            <!-- Actions -->
            <div class="flex gap-2 pt-2">
              <Button
                v-if="offering.status === 'active'"
                variant="outline"
                size="sm"
                @click="openEdit(offering)"
                class="flex-1 gap-2"
              >
                <Edit class="size-4" />
                Edit
              </Button>
              <Button
                v-if="offering.status === 'active'"
                variant="outline"
                size="sm"
                @click="openArchive(offering)"
                class="flex-1 gap-2"
              >
                <Archive class="size-4" />
                Archive
              </Button>
              <Button
                variant="outline"
                size="sm"
                @click="openDelete(offering)"
                class="gap-2"
              >
                <Trash2 class="size-4" />
              </Button>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div
          v-if="offerings!.data.length === 0"
          class="col-span-full flex flex-col items-center justify-center rounded-lg border border-dashed py-16 text-center"
        >
          <Plus class="mb-4 size-12 text-muted-foreground/50" />
          <h3 class="mb-1 font-semibold">No offerings found</h3>
          <p class="text-sm text-muted-foreground">
            Create your first offering to start selling
          </p>
        </div>
      </div>
      <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        <Skeleton v-for="i in 8" :key="i" class="h-96 rounded-lg" />
      </div>
    </div>
  </AppLayout>

  <!-- Offering Form Dialog -->
  <FarmerOfferingForm
    v-if="!isLoadingOptions"
    :open="formOpen"
    :offering="activeOffering"
    :variety-options="varietyOptions!"
    :is-submitting="isSubmitting"
    @update:open="formOpen = $event"
    @submit="handleSubmit"
  />

  <!-- Archive Confirmation -->
  <AlertDialog :open="archiveDialogOpen" @update:open="archiveDialogOpen = $event">
    <AlertDialogContent>
      <AlertDialogHeader>
        <AlertDialogTitle>Archive Offering?</AlertDialogTitle>
        <AlertDialogDescription>
          This will hide the offering from dealers. You can still view it in the "Archived" tab.
        </AlertDialogDescription>
      </AlertDialogHeader>
      <AlertDialogFooter>
        <AlertDialogCancel>Cancel</AlertDialogCancel>
        <AlertDialogAction @click="handleArchive">
          Archive
        </AlertDialogAction>
      </AlertDialogFooter>
    </AlertDialogContent>
  </AlertDialog>

  <!-- Delete Confirmation -->
  <AlertDialog :open="deleteDialogOpen" @update:open="deleteDialogOpen = $event">
    <AlertDialogContent>
      <AlertDialogHeader>
        <AlertDialogTitle>Delete Offering?</AlertDialogTitle>
        <AlertDialogDescription>
          This action cannot be undone. The offering will be permanently deleted.
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
