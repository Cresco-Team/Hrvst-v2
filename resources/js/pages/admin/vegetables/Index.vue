<script setup lang="ts">
import { Deferred, Head, router, useForm } from '@inertiajs/vue3'
import { AlertTriangle } from 'lucide-vue-next'
import { computed, ref } from 'vue'
import ConfirmationDialog from '@/components/dialogs/ConfirmationDialog.vue'
import DialogForm from '@/components/dialogs/DialogForm.vue'
import PriceFreshnessFilter from '@/components/features/admin/filters/PriceFreshnessFilter.vue'
import PriceUpdateForm from '@/components/features/admin/forms/PriceUpdateForm.vue'
import VarietyForm from '@/components/features/admin/forms/VarietyForm.vue'
import VarietyTable from '@/components/features/admin/tables/VarietyTable.vue'
import Heading from '@/components/Heading.vue'
import InputError from '@/components/InputError.vue'
import LargeCard from '@/components/shared/cards/LargeCard.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Skeleton } from '@/components/ui/skeleton'
import AppLayout from '@/layouts/AppLayout.vue'
import admin, { dashboard } from '@/routes/admin'
import {
	destroy as destroyVeg,
	index,
	store as storeVeg,
	update as updateVeg,
} from '@/routes/admin/vegetables'
import type { AdminVegetablesProps, BreadcrumbItem, VarietyResource } from '@/types'
import { mapVegetablesToTableRows, type VarietyTableRow } from '@/types/resources/product'

const props = defineProps<AdminVegetablesProps>()

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
	{ title: 'Admin', href: dashboard().url },
	{ title: 'Vegetables', href: admin.categories.index().url },
	...(props.category
		? [
				{
					title: props.category.name,
					href: index({ query: { category: props.category.slug } }).url,
				},
			]
		: [{ title: 'All Varieties', href: index().url }]),
])

const searchQuery = ref(props.filters?.search ?? '')

const tableVegetables = computed(() => mapVegetablesToTableRows(props.vegetables.data ?? []))

// ── Variety CRUD ───────────────────────────────────────────────────────────────

const varietyFormOpen = ref(false)
const varietyDeleteOpen = ref(false)
const activeVariety = ref<VarietyResource | null>(null)
const activeParentVegetable = ref<{ id: number; name: string } | null>(null)
const varietyDeleteTarget = ref<VarietyTableRow | null>(null)

function openCreateVariety(parentRow: VarietyTableRow): void {
	activeVariety.value = null
	activeParentVegetable.value = { id: parentRow.id, name: parentRow.name }
	varietyFormOpen.value = true
}

function openEditVariety(row: VarietyTableRow): void {
	const parentVeg = tableVegetables.value.find((v) => v.id === row.vegetable_id)
	activeParentVegetable.value = parentVeg ? { id: parentVeg.id, name: parentVeg.name } : null
	activeVariety.value = {
		id: row.id,
		name: row.name,
		image_url: row.image_url ?? '',
		hearts_count: 0,
		is_hearted: false,
		vegetable: { id: row.vegetable_id ?? 0, name: activeParentVegetable.value?.name ?? '', category: null },
		latest_price: row.latest_price ?? null,
	} as unknown as VarietyResource
	varietyFormOpen.value = true
}

function openDeleteVariety(row: VarietyTableRow): void {
	varietyDeleteTarget.value = row
	varietyDeleteOpen.value = true
}

function handleDeleteVariety(): void {
	if (!varietyDeleteTarget.value) return
	router.delete(admin.vegetables.varieties.destroy({ variety: varietyDeleteTarget.value.id }).url, {
		preserveScroll: true,
		onSuccess: () => {
			varietyDeleteOpen.value = false
			varietyDeleteTarget.value = null
		},
	})
}

// ── Vegetable CRUD ─────────────────────────────────────────────────────────────

interface VegFormData {
	category_id: number | string
	name: string
}

const vegFormOpen = ref(false)
const vegDeleteOpen = ref(false)
const vegEditTarget = ref<VarietyTableRow | null>(null)
const vegDeleteTarget = ref<VarietyTableRow | null>(null)

const vegForm = useForm<VegFormData>({
	category_id: props.category?.id ?? '',
	name: '',
})

function openCreateVegetable(): void {
	vegEditTarget.value = null
	vegForm.category_id = props.category?.id ?? ''
	vegForm.name = ''
	vegForm.clearErrors()
	vegFormOpen.value = true
}

function openEditVegetable(row: VarietyTableRow): void {
	vegEditTarget.value = row
	vegForm.category_id = row.category?.id ?? ''
	vegForm.name = row.name
	vegForm.clearErrors()
	vegFormOpen.value = true
}

function openDeleteVegetable(row: VarietyTableRow): void {
	vegDeleteTarget.value = row
	vegDeleteOpen.value = true
}

function handleVegSubmit(): void {
	const isEdit = vegEditTarget.value !== null

	if (isEdit) {
		vegForm.put(updateVeg(vegEditTarget.value!.id).url, {
			preserveScroll: true,
			onSuccess: () => {
				vegFormOpen.value = false
			},
		})
	} else {
		vegForm.post(storeVeg().url, {
			preserveScroll: true,
			onSuccess: () => {
				vegFormOpen.value = false
			},
		})
	}
}

function handleDeleteVegetable(): void {
	if (!vegDeleteTarget.value) return
	router.delete(destroyVeg(vegDeleteTarget.value.id).url, {
		preserveScroll: true,
		onSuccess: () => {
			vegDeleteOpen.value = false
			vegDeleteTarget.value = null
		},
	})
}

// ── Price update ───────────────────────────────────────────────────────────────

const priceFormOpen = ref(false)
const priceVariety = ref<VarietyResource | null>(null)

function openUpdatePrice(row: VarietyTableRow): void {
	priceVariety.value = {
		id: row.id,
		name: row.name,
		image_url: row.image_url ?? '',
		hearts_count: 0,
		is_hearted: false,
		vegetable: { id: 0, name: '', category: null },
		latest_price: row.latest_price ?? null,
	} as unknown as VarietyResource
	priceFormOpen.value = true
}

// ── Filtering ─────────────────────────────────────────────────────────────────

function handleFilterChange(filter: string | null): void {
	router.get(
		index().url,
		{
			price_filter: filter,
			category_id: props.filters.category_id ?? undefined,
		},
		{ preserveScroll: true, preserveState: true },
	)
}

function handleSearch(query: string): void {
	searchQuery.value = query
	router.visit(index().url, {
		data: {
			search: query || undefined,
			price_filter: props.filters.price_filter || undefined,
			category_id: props.filters.category_id ?? undefined,
		},
		preserveState: true,
		preserveScroll: true,
		only: ['vegetables', 'filters'],
	})
}
</script>

<template>
  <Head title="Vegetables" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-col gap-6 p-4 lg:p-6">

      <div class="flex items-end justify-between">
        <Heading
          title="Vegetables"
          :description="category ? `Showing vegetables in ${category.name}` : 'Manage all vegetable types and their varieties.'"
        />
        <div class="flex items-center gap-2">
          <PriceFreshnessFilter
            v-if="summary"
            :active-filter="filters.price_filter"
            :price-stats="summary.price_stats"
            @filter-change="handleFilterChange"
          />
          <Skeleton v-else class="h-9 w-32" />
          <Button @click="openCreateVegetable">
            Add Vegetable
          </Button>
        </div>
      </div>

      <!-- Summary cards -->
      <Deferred data="summary">
        <template #fallback>
          <div class="grid gap-4 md:grid-cols-3">
            <Skeleton v-for="i in 3" :key="i" class="h-32" />
          </div>
        </template>
        <div class="grid gap-4 md:grid-cols-3">
          <LargeCard
            title="Vegetable Varieties"
            subtext="total"
            :value="summary?.total_varieties"
          />
          <LargeCard
            title="Price Updates"
            subtext="updated this week"
            :value="summary?.price_stats.updated_week"
          />
          <LargeCard
            title="Needs Attention"
            subtext="varieties"
            :value="summary?.price_stats.stale"
            :icon="AlertTriangle"
            icon-color="text-orange-500"
          />
        </div>
      </Deferred>

      <!-- Hierarchical table -->
      <Deferred data="vegetables">
        <template #fallback>
          <div class="flex flex-col gap-3">
            <Skeleton class="h-9 w-72" />
            <div class="rounded-lg border">
              <div class="space-y-2 p-4">
                <Skeleton v-for="i in 6" :key="i" class="h-11 w-full" />
              </div>
            </div>
          </div>
        </template>

        <VarietyTable
          v-if="vegetables"
          :vegetables="tableVegetables"
          :search-query="searchQuery"
          @open-edit-vegetable="openEditVegetable"
          @open-delete-vegetable="openDeleteVegetable"
          @open-create-variety="openCreateVariety"
          @open-edit-variety="openEditVariety"
          @open-delete-variety="openDeleteVariety"
          @open-update-price="openUpdatePrice"
          @open-variety-details="(row) => router.visit(admin.vegetables.varieties.show({ variety: row.id }).url)"
          @search="handleSearch"
        />
      </Deferred>

    </div>
  </AppLayout>

  <!-- Variety create / edit -->
  <VarietyForm
    :open="varietyFormOpen"
    :variety="activeVariety"
    :parent-vegetable="activeParentVegetable"
    :store-url="admin.vegetables.varieties.store().url"
    :update-url="activeVariety ? admin.vegetables.varieties.update({ variety: activeVariety.id }).url : undefined"
    @update:open="varietyFormOpen = $event"
    @success="varietyFormOpen = false"
  />

  <!-- Variety delete -->
  <ConfirmationDialog
    v-model:open="varietyDeleteOpen"
    title="Delete Variety"
    :description="`Are you sure you want to delete '${varietyDeleteTarget?.name}'?`"
    variant="destructive"
    @action="handleDeleteVariety"
  />

  <!-- Vegetable create / edit -->
  <DialogForm
    :open="vegFormOpen"
    :form="vegForm"
    :title="vegEditTarget ? 'Edit Vegetable' : 'Add Vegetable'"
    :description="vegEditTarget
      ? 'Update the vegetable name or category.'
      : 'Create a new vegetable type under a category.'"
    :submit-label="vegEditTarget ? 'Save Changes' : 'Create Vegetable'"
    max-width="md"
    @update:open="!$event && (vegFormOpen = false)"
    @submit="handleVegSubmit"
  >
    <template #default>
      <div class="flex flex-col gap-4">
        <div class="flex flex-col gap-2">
          <Label for="veg-name">Name</Label>
          <Input
            id="veg-name"
            v-model="vegForm.name"
            placeholder="e.g. Pechay, Kangkong, Carrot"
            :class="{ 'border-destructive': vegForm.errors.name }"
          />
          <InputError :message="vegForm.errors.name" />
        </div>
      </div>
    </template>
  </DialogForm>

  <!-- Vegetable delete -->
  <ConfirmationDialog
    v-model:open="vegDeleteOpen"
    title="Delete Vegetable"
    :description="(vegDeleteTarget?.varieties?.length ?? 0) > 0
      ? `'${vegDeleteTarget?.name}' still has varieties. Remove them first.`
      : `Are you sure you want to delete '${vegDeleteTarget?.name}'?`"
    :action-name="(vegDeleteTarget?.varieties?.length ?? 0) > 0 ? 'OK' : 'Delete'"
    :variant="(vegDeleteTarget?.varieties?.length ?? 0) > 0 ? 'default' : 'destructive'"
    @action="(vegDeleteTarget?.varieties?.length ?? 0) > 0 ? (vegDeleteOpen = false) : handleDeleteVegetable()"
  />

  <!-- Price update -->
  <PriceUpdateForm
    v-if="priceVariety"
    :open="priceFormOpen"
    :variety="priceVariety"
    :is-submitting="false"
    @update:open="priceFormOpen = $event"
  />
</template>
