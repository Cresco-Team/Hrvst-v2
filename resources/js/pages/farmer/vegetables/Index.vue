<script setup lang="ts">
import { Deferred, Head, router } from '@inertiajs/vue3'
import { Search } from 'lucide-vue-next'
import type { AcceptableValue } from 'reka-ui'
import { ref } from 'vue'
import EmptyState from '@/components/EmptyState.vue'
import Heading from '@/components/Heading.vue'
import VegetableCatalogCard from '@/components/shared/VegetableCatalogCard.vue'
import VegetableDetailDialog from '@/components/shared/VegetableDetailDialog.vue'
import { Button } from '@/components/ui/button'
import {
	InputGroup,
	InputGroupAddon,
	InputGroupInput,
} from '@/components/ui/input-group'
import {
	Select,
	SelectContent,
	SelectItem,
	SelectTrigger,
	SelectValue,
} from '@/components/ui/select'
import { Skeleton } from '@/components/ui/skeleton'
import AppLayout from '@/layouts/AppLayout.vue'
import farmer from '@/routes/farmer'
import type { PaginatedResponse } from '@/types/pagination'
import type {
	CatalogFilters,
	CatalogVariety,
	CategoryOption,
} from '@/types/shared/vegetables'

interface Props {
	filters: CatalogFilters
	varieties?: PaginatedResponse<CatalogVariety>
	categoryOptions?: CategoryOption[]
}

const props = defineProps<Props>()

const selectedVariety = ref<CatalogVariety | null>(null)
const dialogOpen = ref(false)

const searchQuery = ref(props.filters.search ?? '')
let searchDebounce: ReturnType<typeof setTimeout> | null = null

const breadcrumbs = [
	{ title: 'Farmer', href: farmer.garden.index().url },
	{ title: 'Vegetables', href: farmer.vegetables.index().url },
]

function openDetail(variety: CatalogVariety) {
	selectedVariety.value = variety
	dialogOpen.value = true
}

function handleSearch() {
	if (searchDebounce) clearTimeout(searchDebounce)

	searchDebounce = setTimeout(() => {
		router.visit(farmer.vegetables.index().url, {
			data: {
				search: searchQuery.value || undefined,
				category_id: props.filters.category_id || undefined,
			},
			preserveState: true,
			preserveScroll: true,
			only: ['varieties'],
		})
	}, 300)
}

function handleCategoryFilter(value: AcceptableValue) {
	const category = value === 'all' || value == null ? undefined : String(value)

	router.visit(farmer.vegetables.index().url, {
		data: {
			search: props.filters.search || undefined,
			category_id: category,
		},
		preserveState: true,
		preserveScroll: true,
		only: ['varieties'],
	})
}

function handlePageChange(page: number) {
	router.visit(farmer.vegetables.index().url, {
		data: {
			page,
			search: props.filters.search || undefined,
			category_id: props.filters.category_id || undefined,
		},
		preserveScroll: true,
	})
}
</script>

<template>
  <Head title="Vegetables" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-col gap-6 p-4 lg:p-6">

      <!-- Header -->
      <Heading
        title="Vegetables"
        description="Browse all available varieties and their current market prices."
      />

      <!-- Filters -->
      <div class="flex flex-wrap gap-3">
        <InputGroup class="w-full sm:max-w-xs">
          <InputGroupAddon>
            <Search class="size-4 text-muted-foreground" />
          </InputGroupAddon>
          <InputGroupInput
            v-model="searchQuery"
            placeholder="Search varieties..."
            @input="handleSearch"
          />
          <InputGroupAddon align="inline-end">
            {{ varieties?.meta.total }} results
          </InputGroupAddon>
        </InputGroup>

        <Deferred data="categoryOptions">
          <template #fallback>
            <Skeleton class="h-9 w-40" />
          </template>

          <Select
            :model-value="String(filters.category_id ?? 'all')"
            @update:model-value="handleCategoryFilter"
          >
            <SelectTrigger class="w-40">
              <SelectValue placeholder="All categories" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">All categories</SelectItem>
              <SelectItem
                v-for="cat in categoryOptions"
                :key="cat.id"
                :value="String(cat.id)"
              >
                {{ cat.name }}
              </SelectItem>
            </SelectContent>
          </Select>
        </Deferred>
      </div>

      <!-- Card grid -->
      <Deferred data="varieties">
        <template #fallback>
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
            <Skeleton v-for="i in 8" :key="i" class="aspect-4/3 rounded-xl" />
          </div>
        </template>

        <EmptyState
          v-if="!varieties?.data.length"
          title="No varieties found"
          description="Try adjusting your search or category filter."
        />

        <div
          v-else
          class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4"
        >
          <VegetableCatalogCard
            v-for="variety in varieties.data"
            :key="variety.id"
            :variety="variety"
            @select="openDetail"
          />
        </div>
      </Deferred>

      <!-- Pagination -->
      <div
        v-if="varieties && varieties.meta.last_page > 1"
        class="flex items-center justify-between border-t pt-4"
      >
        <Button
          variant="outline"
          size="sm"
          :disabled="varieties.meta.current_page === 1"
          @click="handlePageChange(varieties.meta.current_page - 1)"
        >
          Previous
        </Button>
        <span class="text-sm text-muted-foreground">
          Page {{ varieties.meta.current_page }} of {{ varieties.meta.last_page }}
        </span>
        <Button
          variant="outline"
          size="sm"
          :disabled="varieties.meta.current_page === varieties.meta.last_page"
          @click="handlePageChange(varieties.meta.current_page + 1)"
        >
          Next
        </Button>
      </div>

    </div>
  </AppLayout>

  <!-- Detail dialog — lives outside AppLayout to avoid stacking context issues -->
  <VegetableDetailDialog
    :open="dialogOpen"
    :variety="selectedVariety"
    @update:open="dialogOpen = $event"
  />
</template>
