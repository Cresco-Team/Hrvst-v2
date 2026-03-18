<script setup lang="ts">
import { Deferred, Head, router } from '@inertiajs/vue3'
import { Archive, CalendarClock, PackageCheck, Plus, Sprout, Wheat } from 'lucide-vue-next'
import { computed, ref } from 'vue'
import ConfirmationDialog from '@/components/ConfirmationDialog.vue'
import EmptyState from '@/components/EmptyState.vue'
import SupplyCard from '@/components/farmer/cards/SupplyCard.vue'
import SupplyForm from '@/components/farmer/SupplyForm.vue'
import Heading from '@/components/Heading.vue'
import LargeCard from '@/components/shared/cards/LargeCard.vue'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs'
import AppLayout from '@/layouts/AppLayout.vue'
import farmer from '@/routes/farmer'
import { archive, destroy, fulfill, index } from '@/routes/farmer/garden'
import type { Supply, SupplySummary, VarietyOption } from '@/types/marketplace'
import type { PaginatedResponse } from '@/types/pagination'

interface Props {
	filters: { status: string }
	summary?: SupplySummary
	varietyOptions?: Record<string, VarietyOption[]>
	supplies?: PaginatedResponse<Supply>
}

const props = defineProps<Props>()

/* --- State --------------- */

const formOpen = ref(false)
const activeSupply = ref<Supply | null>(null)

const archiveDialogOpen = ref(false)
const fulfillDialogOpen = ref(false)
const deleteDialogOpen = ref(false)
const supplyToArchive = ref<Supply | null>(null)
const supplyToFulfill = ref<Supply | null>(null)
const supplyToDelete = ref<Supply | null>(null)

/* --- Computed --------------- */

const activeTab = computed(() => props.filters.status ?? 'Ongoing')

/* --- Actions --------------- */

function handleTabChange(value: string | number) {
	const routeTarget = index({
		query: { status: value === 'Ongoing' ? undefined : value },
	})

	router.visit(routeTarget.url, {
		preserveState: true,
		preserveScroll: true,
		only: ['supplies', 'filters', 'summary'],
	})
}

function openCreate() {
	activeSupply.value = null
	formOpen.value = true
}

function openEdit(supply: Supply) {
	activeSupply.value = supply
	formOpen.value = true
}

function openArchive(supply: Supply) {
	supplyToArchive.value = supply
	archiveDialogOpen.value = true
}

function openFulfill(supply: Supply) {
	supplyToFulfill.value = supply
	fulfillDialogOpen.value = true
}

function openDelete(supply: Supply) {
	supplyToDelete.value = supply
	deleteDialogOpen.value = true
}

function handleArchive() {
	if (!supplyToArchive.value) return

	const route = archive(supplyToArchive.value.id)
	router.post(
		route.url,
		{},
		{
			preserveScroll: true,
			onSuccess: () => {
				archiveDialogOpen.value = false
				supplyToArchive.value = null
			},
		},
	)
}

const handleFulfill = () => {
	if (!supplyToFulfill.value) return

	const route = fulfill(supplyToFulfill.value.id)
	router.post(
		route.url,
		{},
		{
			preserveScroll: true,
			onSuccess: () => {
				fulfillDialogOpen.value = false
				supplyToFulfill.value = null
			},
		},
	)
}

function handleDelete() {
	if (!supplyToDelete.value) return

	const routeTarget = destroy(supplyToDelete.value.id)

	router.visit(routeTarget.url, {
		method: 'delete',
		preserveScroll: true,
		onSuccess: () => {
			deleteDialogOpen.value = false
			supplyToDelete.value = null
		},
	})
}

function handlePageChange(page: number) {
	router.visit(farmer.garden.index().url, {
		data: {
			page,
			search: props.filters.status || undefined,
		},
		preserveScroll: true,
	})
}

const breadcrumbs = [
	{ title: 'Farmer', href: farmer.garden.index().url },
	{ title: 'Garden', href: farmer.garden.index().url },
]
</script>

<template>

  <Head title="My Garden" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-col gap-6 p-4 lg:p-6">
      <!-- Header -->
      <div class="flex items-end justify-between">
        <Heading title="My Garden" description="Manage your vegetable posts for dealers." />

        <Button @click="openCreate" class="gap-2">
          <Plus class="size-4" />
          New Post
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
          <LargeCard title="Ongoing Posts" :value="summary?.total_ongoing" subtext="all available offers" :icon="Wheat"
            card-class="from-emerald-50 to-green-50 dark:from-emerald-950/20 dark:to-green-950/20" />

          <LargeCard title="Fulfilled Posts" :value="summary?.total_fulfilled" subtext="all fulfilled offers"
            :icon="PackageCheck" card-class="from-zinc-100 to-zinc-100 dark:from-zinc-950/20 dark:to-zinc-950/20" />

          <LargeCard title="Archived Posts" :value="summary?.total_archived" subtext="all archived offers"
            :icon="Archive" card-class="from-amber-50 to-orange-50 dark:from-emerald-950/20 dark:to-green-950/20" />

          <LargeCard title="Expiring Posts" :value="summary?.scheduled_this_week" subtext="expiring this week"
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

      <!-- supplies Grid -->
      <Deferred data="supplies">
        <template #fallback>
          <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            <Skeleton v-for="i in 8" :key="i" class="h-96 rounded-lg" />
          </div>
        </template>

        <EmptyState v-if="supplies?.data.length === 0" title="No Posted supplies Yet." description="Post an offering"
          :icon="Sprout" button="Add Offering" />

        <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
          <SupplyCard v-for="supply in supplies!.data" :key="supply.id" :supply="supply"
            :variety-options="varietyOptions" @edit="openEdit" @archive="openArchive" @fulfill="openFulfill"
            @delete="openDelete" />
        </div>
      </Deferred>

      <div v-if="supplies && supplies.meta.last_page > 1" class="flex items-center justify-between border-t pt-4">
        <Button variant="outline" size="sm" :disabled="supplies.meta.current_page === 1"
          @click="handlePageChange(supplies.meta.current_page - 1)">
          Previous
        </Button>
        <span class="text-sm text-muted-foreground">
          Page {{ supplies.meta.current_page }} of {{ supplies.meta.last_page }}
        </span>
        <Button variant="outline" size="sm" :disabled="supplies.meta.current_page === supplies.meta.last_page"
          @click="handlePageChange(supplies.meta.current_page + 1)">
          Next
        </Button>
      </div>
    </div>
  </AppLayout>

  <!-- Offering Form Dialog -->
  <SupplyForm :open="formOpen" :supply="activeSupply" :variety-options="varietyOptions!"
    @update:open="formOpen = $event" />

  <!-- Archive Confirmation -->
  <ConfirmationDialog v-model:open="archiveDialogOpen" title="Archive Post"
    :description="`Are you sure you want to archive ${supplyToArchive?.variety.vegetable} ${supplyToArchive?.variety.name}?`"
    variant="destructive" @action="handleArchive" />

  <ConfirmationDialog v-model:open="fulfillDialogOpen" title="Fulfill Post"
    :description="`Are you sure you want to set ${supplyToFulfill?.variety.vegetable} ${supplyToFulfill?.variety.name} post as fulfilled?`"
    @action="handleFulfill" />

  <!-- Delete Confirmation -->
  <ConfirmationDialog v-model:open="deleteDialogOpen" title="Delete Post"
    :description="`Are you sure you want to delete ${supplyToDelete?.variety.vegetable} ${supplyToDelete?.variety.name}`"
    @action="handleDelete" />
</template>
