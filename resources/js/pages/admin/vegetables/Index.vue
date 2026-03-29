<script setup lang="ts">
import { Deferred, Head, router } from '@inertiajs/vue3'
import axios from 'axios'
import { AlertTriangle, Leaf, Sprout, TrendingUp } from 'lucide-vue-next'
import { ref } from 'vue'
import { toast } from 'vue-sonner'
import ConfirmationDialog from '@/components/dialogs/ConfirmationDialog.vue'
import EmptyState from '@/components/EmptyState.vue'
import PriceFreshnessFilter from '@/components/features/admin/filters/PriceFreshnessFilter.vue'
import PriceUpdateForm from '@/components/features/admin/forms/PriceUpdateForm.vue'
import VarietyForm from '@/components/features/admin/forms/VarietyForm.vue'
import VarietyTable from '@/components/features/admin/tables/VarietyTable.vue'
import Heading from '@/components/Heading.vue'
import LargeCard from '@/components/shared/cards/LargeCard.vue'
import { Skeleton } from '@/components/ui/skeleton'
import AppLayout from '@/layouts/AppLayout.vue'
import { dashboard } from '@/routes/admin'
import {
	destroy,
	index,
	store,
	update,
	details as varietyDetails,
	show as varietyShow,
} from '@/routes/admin/vegetables'
import type { AdminVegetablesProps, BreadcrumbItem, VarietyResource } from '@/types'

const props = withDefaults(defineProps<AdminVegetablesProps>(), {
	varieties: undefined,
	summary: undefined,
	vegetableOptions: undefined,
})

const breadcrumbs: BreadcrumbItem[] = [
	{ title: 'Admin', href: dashboard().url },
	{ title: 'Vegetables', href: index().url },
]

const searchQuery = ref(props.filters?.search ?? '')

/* -- Variety CRUD state -- */
const formOpen = ref(false)
const deleteOpen = ref(false)
const activeVariety = ref<VarietyResource | null>(null)
const isSubmitting = ref(false)

/* -- Update Price state -- */
const priceOpen = ref(false)
const priceVariety = ref<VarietyResource | null>(null)
const isPriceSubmitting = ref(false)

function openCreate() {
	activeVariety.value = null
	formOpen.value = true
}

function openEdit(variety: VarietyResource) {
	activeVariety.value = variety
	formOpen.value = true
}

function openDelete(variety: VarietyResource) {
	activeVariety.value = variety
	deleteOpen.value = true
}

function openUpdatePrice(variety: VarietyResource) {
	priceVariety.value = variety
	priceOpen.value = true
}

/* -- Filtering -- */
function handleFilterChange(filter: string | null) {
	router.get(index().url, { price_filter: filter }, { preserveScroll: true, preserveState: true })
}

function handleSearch(query: string) {
	searchQuery.value = query
	router.visit(index().url, {
		data: {
			search: query || undefined,
			price_filter: props.filters.price_filter || undefined,
		},
		preserveState: true,
		preserveScroll: true,
		only: ['varieties', 'filters'],
	})
}

/* -- Variety CRUD -- */
function handleSubmit(formData: FormData) {
	isSubmitting.value = true

	if (activeVariety.value) {
		formData.append('_method', 'PUT')
		router.post(update({ variety: activeVariety.value.id }).url, formData, {
			onSuccess() {
				formOpen.value = false
				isSubmitting.value = false
			},
			onError() {
				isSubmitting.value = false
			},
		})
	} else {
		router.post(store().url, formData, {
			onSuccess() {
				formOpen.value = false
				isSubmitting.value = false
			},
			onError() {
				isSubmitting.value = false
			},
		})
	}
}

function handleDelete() {
	if (!activeVariety.value) return
	router.delete(destroy({ variety: activeVariety.value.id }).url, {
		onSuccess() {
			deleteOpen.value = false
			activeVariety.value = null
		},
	})
}

function handlePageChange(page: number) {
	router.get(
		index().url,
		{
			page,
			price_filter: props.filters.price_filter,
			search: searchQuery.value || undefined,
		},
		{ preserveScroll: true },
	)
}
</script>

<template>

  <Head title="Vegetables" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-col gap-6 p-4 lg:p-6">

      <div class="flex items-end justify-between">
        <Heading title="Vegetables" description="Manage all vegetable varieties and prices." />
        <PriceFreshnessFilter v-if="summary" :active-filter="filters.price_filter" :price-stats="summary.price_stats"
          @filter-change="handleFilterChange" />
        <Skeleton v-else class="h-9 w-32" />
      </div>

      <!-- Summary cards -->
      <Deferred data="summary">
        <template #fallback>
          <div class="grid gap-4 md:grid-cols-3">
            <Skeleton class="h-33" />
            <Skeleton class="h-33" />
            <Skeleton class="h-33" />
          </div>
        </template>

        <div class="grid gap-4 md:grid-cols-3">
          <LargeCard title="Total Varieties" subtext="available for planting" :value="summary?.total_varieties"
            :icon="Leaf" cardClass="md:col-span-1 bg-linear-to-br from-lime-500/10 to-green-500/30" />
          <LargeCard title="Price Updates" subtext="this week" :value="summary?.price_stats.updated_week"
            :icon="TrendingUp" cardClass="md:col-span-1 bg-linear-to-br from-lime-500/10 to-green-500/30" />
          <LargeCard title="Needs Attention" subtext="varieties" :value="summary?.price_stats.stale"
            :icon="AlertTriangle" iconColor="text-orange-500"
            cardClass="md:col-span-1 bg-linear-to-br from-red-500/20 via-green-500/10 to-green-500/30" />
        </div>
      </Deferred>

      <!-- Table -->
      <Deferred data="varieties">
        <template #fallback>
          <div class="flex flex-col gap-4">
            <div class="flex items-center justify-between">
              <Skeleton class="h-9 w-64" />
              <Skeleton class="h-9 w-32" />
            </div>
            <div class="rounded-lg border">
              <div class="space-y-3 p-4">
                <Skeleton v-for="n in 5" :key="n" class="h-12 w-full" />
              </div>
            </div>
          </div>
        </template>

        <VarietyTable v-if="varieties" :varieties="varieties" :search-query="searchQuery" @open-create="openCreate"
          @open-edit="openEdit" @open-delete="openDelete" @open-update-price="openUpdatePrice"
          @page-change="handlePageChange" @search="handleSearch" />

        <EmptyState v-else title="No Vegetables Yet" description="Create a vegetable variety to display."
          :icon="Sprout" />
      </Deferred>

    </div>
  </AppLayout>

  <!-- Variety create / edit -->
  <VarietyForm v-if="vegetableOptions" :open="formOpen" :variety="activeVariety" :vegetable-options="vegetableOptions"
    :is-submitting="isSubmitting" @update:open="formOpen = $event" @submit="handleSubmit" />

  <!-- Delete confirm -->
  <ConfirmationDialog v-model:open="deleteOpen" title="Delete Variety"
    :description="`Are you sure you want to delete ${activeVariety?.vegetable?.name} ${activeVariety?.name}?`"
    @action="handleDelete" />

  <!-- Update price -->
  <PriceUpdateForm v-if="priceVariety" :open="priceOpen" :variety="priceVariety" :is-submitting="isPriceSubmitting"
    @update:open="priceOpen = $event" />
</template>
