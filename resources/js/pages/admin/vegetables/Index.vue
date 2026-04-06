<script setup lang="ts">
import { Deferred, Head, router } from '@inertiajs/vue3'
import { AlertTriangle } from 'lucide-vue-next'
import { ref } from 'vue'
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
import {
	Select,
	SelectContent,
	SelectItem,
	SelectTrigger,
	SelectValue,
} from '@/components/ui/select'
import { Skeleton } from '@/components/ui/skeleton'
import AppLayout from '@/layouts/AppLayout.vue'
import admin from '@/routes/admin'
import {
	destroy as destroyVeg,
	store as storeVeg,
	update as updateVeg,
} from '@/routes/admin/vegetables'
import { destroy, index, store, update } from '@/routes/admin/vegetables/varieties'
// Keep importing shared types that are stable; only replace AdminVegetablesProps
import type { BreadcrumbItem, VarietyResource } from '@/types'
import type { Table } from '@/types/resources/product'

interface PriceStats {
	updated_week: number
	updated_month: number
	stale: number
	no_price: number
}

interface Summary {
	total_varieties: number
	total_vegetables: number
	price_stats: PriceStats
}

type VegetableOptions = Record<string, Record<number, string>>

interface Category {
	id: number
	name: string
}

interface Filters {
	price_filter: string | null
	search: string | null
}

const props = defineProps<{
	filters: Filters
	vegetables?: Table[]
	summary?: Summary
	vegetableOptions?: VegetableOptions
	categories?: Category[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
	{ title: 'Admin', href: admin.dashboard().url },
	{ title: 'Vegetables', href: index().url },
]

const searchQuery = ref(props.filters?.search ?? '')

// ── Variety CRUD ───────────────────────────────────────────────────────────────

const varietyFormOpen = ref(false)
const varietyDeleteOpen = ref(false)
const activeVariety = ref<VarietyResource | null>(null)
const varietyDeleteTarget = ref<Table | null>(null)
const isVarietySubmitting = ref(false)

function openCreateVariety(): void {
	activeVariety.value = null
	varietyFormOpen.value = true
}

function openEditVariety(row: Table): void {
	// Reconstruct a minimal VarietyResource shape VarietyForm accepts
	activeVariety.value = {
		id: row.id,
		name: row.name,
		image_url: row.image_url ?? '',
		hearts_count: 0,
		is_hearted: false,
		vegetable: { id: 0, name: '', category: null },
		latest_price: row.latest_price ?? null,
	} as unknown as VarietyResource
	varietyFormOpen.value = true
}

function openDeleteVariety(row: Table): void {
	varietyDeleteTarget.value = row
	varietyDeleteOpen.value = true
}

function handleDeleteVariety(): void {
	if (!varietyDeleteTarget.value) return
	router.delete(destroy({ variety: varietyDeleteTarget.value.id }).url, {
		preserveScroll: true,
		onSuccess: () => {
			varietyDeleteOpen.value = false
			varietyDeleteTarget.value = null
		},
	})
}

function handleVarietyFormSubmit(payload: FormData): void {
	isVarietySubmitting.value = true
	const isEdit = activeVariety.value !== null
	const url = isEdit ? update({ variety: activeVariety.value!.id }).url : store().url
	if (isEdit) payload.append('_method', 'PUT')

	router.post(url, payload, {
		onSuccess: () => {
			varietyFormOpen.value = false
			isVarietySubmitting.value = false
		},
		onError: () => {
			isVarietySubmitting.value = false
		},
	})
}

// ── Vegetable CRUD ─────────────────────────────────────────────────────────────

interface VegFormErrors {
	category_id?: string
	name?: string
}

const vegFormOpen = ref(false)
const vegDeleteOpen = ref(false)
const vegSubmitting = ref(false)
const vegErrors = ref<VegFormErrors>({})
const vegEditTarget = ref<Table | null>(null)
const vegDeleteTarget = ref<Table | null>(null)
const vegCategoryId = ref('')
const vegName = ref('')

function openCreateVegetable(): void {
	vegEditTarget.value = null
	vegCategoryId.value = ''
	vegName.value = ''
	vegErrors.value = {}
	vegFormOpen.value = true
}

function openEditVegetable(row: Table): void {
	vegEditTarget.value = row
	vegCategoryId.value = String(row.category?.id ?? '')
	vegName.value = row.name
	vegErrors.value = {}
	vegFormOpen.value = true
}

function openDeleteVegetable(row: Table): void {
	vegDeleteTarget.value = row
	vegDeleteOpen.value = true
}

function handleVegSubmit(): void {
	vegSubmitting.value = true
	vegErrors.value = {}

	const isEdit = vegEditTarget.value !== null
	const url = isEdit ? updateVeg(vegEditTarget.value!.id).url : storeVeg().url

	router.visit(url, {
		method: isEdit ? 'put' : 'post',
		data: { category_id: vegCategoryId.value, name: vegName.value },
		preserveScroll: true,
		onSuccess: () => {
			vegFormOpen.value = false
		},
		onError: (errors) => {
			vegErrors.value = errors as VegFormErrors
		},
		onFinish: () => {
			vegSubmitting.value = false
		},
	})
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

function openUpdatePrice(row: Table): void {
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
	router.get(index().url, { price_filter: filter }, { preserveScroll: true, preserveState: true })
}

function handleSearch(query: string): void {
	searchQuery.value = query
	router.visit(index().url, {
		data: {
			search: query || undefined,
			price_filter: props.filters.price_filter || undefined,
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
        <Heading title="Vegetables" description="Manage all vegetable types and their varieties." />
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
          :vegetables="vegetables"
          :search-query="searchQuery"
          @open-edit-vegetable="openEditVegetable"
          @open-delete-vegetable="openDeleteVegetable"
          @open-create-variety="openCreateVariety"
          @open-edit-variety="openEditVariety"
          @open-delete-variety="openDeleteVariety"
          @open-update-price="openUpdatePrice"
          @open-variety-details="(row) => router.visit(`/admin/vegetables/${row.id}`)"
          @search="handleSearch"
        />
      </Deferred>

    </div>
  </AppLayout>

  <!-- Variety create / edit -->
  <VarietyForm
    v-if="vegetableOptions"
    :open="varietyFormOpen"
    :variety="activeVariety"
    :vegetable-options="vegetableOptions"
    :is-submitting="isVarietySubmitting"
    @update:open="varietyFormOpen = $event"
    @submit="handleVarietyFormSubmit"
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
    v-if="categories"
    :open="vegFormOpen"
    :title="vegEditTarget ? 'Edit Vegetable' : 'Add Vegetable'"
    :description="vegEditTarget
      ? 'Update the vegetable name or category.'
      : 'Create a new vegetable type under a category.'"
    :is-submitting="vegSubmitting"
    :submit-label="vegEditTarget ? 'Save Changes' : 'Create Vegetable'"
    max-width="md"
    @update:open="!$event && (vegFormOpen = false)"
    @submit="handleVegSubmit"
  >
    <Deferred data="categories">
      <template #fallback>
        <Skeleton class="h-9 w-full" />
      </template>
      <div class="flex flex-col gap-4">
        <div class="flex flex-col gap-2">
          <Label for="veg-category">Category</Label>
          <Select v-model="vegCategoryId">
            <SelectTrigger
              id="veg-category"
              :class="{ 'border-destructive': vegErrors.category_id }"
            >
              <SelectValue placeholder="Select a category…" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem
                v-for="cat in categories"
                :key="cat.id"
                :value="String(cat.id)"
              >
                {{ cat.name }}
              </SelectItem>
            </SelectContent>
          </Select>
          <InputError :message="vegErrors.category_id" />
        </div>
        <div class="flex flex-col gap-2">
          <Label for="veg-name">Name</Label>
          <Input
            id="veg-name"
            v-model="vegName"
            placeholder="e.g. Pechay, Kangkong, Carrot"
            :class="{ 'border-destructive': vegErrors.name }"
          />
          <InputError :message="vegErrors.name" />
        </div>
      </div>
    </Deferred>
  </DialogForm>

  <!-- Vegetable delete -->
  <ConfirmationDialog
    v-model:open="vegDeleteOpen"
    title="Delete Vegetable"
    :description="(vegDeleteTarget?.subRows?.length ?? 0) > 0
      ? `'${vegDeleteTarget?.name}' still has varieties. Remove them first.`
      : `Are you sure you want to delete '${vegDeleteTarget?.name}'?`"
    :action-name="(vegDeleteTarget?.subRows?.length ?? 0) > 0 ? 'OK' : 'Delete'"
    :variant="(vegDeleteTarget?.subRows?.length ?? 0) > 0 ? 'default' : 'destructive'"
    @action="(vegDeleteTarget?.subRows?.length ?? 0) > 0 ? (vegDeleteOpen = false) : handleDeleteVegetable()"
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
