<script setup lang="ts">
import { ref, computed, onMounted, toRaw } from 'vue'
import { Head, router, Link } from '@inertiajs/vue3'
import { Search, Filter, ShoppingCart } from 'lucide-vue-next'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Skeleton } from '@/components/ui/skeleton'
import OfferingCard from '@/components/dealer/OfferingCard.vue'
import AppLayout from '@/layouts/AppLayout.vue'
import Heading from '@/components/Heading.vue'
import dealer from '@/routes/dealer'
import { PaginatedResponse } from '@/types/pagination'
import { CategoryOption, MarketplaceFilters, MunicipalityOption, Planting } from '@/types/dealer/marketplace'

interface Props {
  filters: MarketplaceFilters
  plantings?: PaginatedResponse<Planting>
  filterOptions?: {
    categories: CategoryOption[]
    municipalities: MunicipalityOption[]
  }
}

const props = defineProps<Props>()

onMounted(() => console.log(props.plantings))

console.log(toRaw(props))

const searchQuery = ref(props.filters.search || '')
const searchDebounce = ref<ReturnType<typeof setTimeout> | null>(null)

const isLoadingOfferings = computed(() => !props.plantings)
const isLoadingFilters = computed(() => !props.filterOptions)

function handleSearch() {
  if (searchDebounce.value) clearTimeout(searchDebounce.value)

  searchDebounce.value = setTimeout(() => {
    router.visit(dealer.marketplace.index().url, {
      data: {
        search: searchQuery.value || undefined,
        category_id: props.filters.category_id || undefined,
        municipality_id: props.filters.municipality_id || undefined,
      },
      preserveState: true,
      preserveScroll: true,
      only: ['offerings'],
    })
  }, 300)
}

function handleFilter(type: 'category' | 'municipality', value: string) {
  const filters: Record<string, string | undefined> = {
    search: searchQuery.value || undefined,
    category_id: undefined,
    municipality_id: undefined,
  }

  if (type === 'category') {
    filters.category_id = value === 'all' ? undefined : value
    filters.municipality_id = props.filters.municipality_id?.toString()
  } else {
    filters.municipality_id = value === 'all' ? undefined : value
    filters.category_id = props.filters.category_id?.toString()
  }

  router.visit(dealer.marketplace.index().url, {
    data: filters,
    preserveState: true,
    preserveScroll: true,
    only: ['offerings'],
  })
}

function handlePageChange(page: number) {
  router.visit(dealer.marketplace.index().url, {
    data: {
      page,
      search: searchQuery.value || undefined,
      category_id: props.filters.category_id || undefined,
      municipality_id: props.filters.municipality_id || undefined,
    },
    preserveScroll: true,
  })
}

const breadcrumbs = [
  { title: 'Dealer', href: dealer.market().url },
  { title: 'Marketplace', href: dealer.marketplace.index().url },
]
</script>

<template>
  <Head title="Marketplace" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-col gap-6 p-4 lg:p-6">
      <!-- Header -->
      <div class="flex items-start justify-between">
        <Heading
          title="Marketplace"
          description="Browse active farmer offerings and connect with local producers."
        >
          <template #icon>
            <ShoppingCart class="size-8" />
          </template>
        </Heading>
      </div>

      <!-- Filters -->
      <div class="flex flex-col gap-4 md:flex-row">
        <!-- Search -->
        <div class="relative flex-1">
          <Search class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
          <Input
            v-model="searchQuery"
            type="search"
            placeholder="Search varieties (e.g., Cabbage, Lettuce)..."
            class="pl-10"
            @input="handleSearch"
          />
        </div>

        <!-- Category filter -->
        <Select
          :model-value="filters.category_id?.toString() || 'all'"
          :disabled="isLoadingFilters"
          @update:model-value="(v) => handleFilter('category', v as string)"
        >
          <SelectTrigger class="w-full md:w-48">
            <SelectValue placeholder="All Categories" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">All Categories</SelectItem>
            <SelectItem
              v-for="category in filterOptions?.categories"
              :key="category.id"
              :value="category.id.toString()"
            >
              {{ category.name }}
            </SelectItem>
          </SelectContent>
        </Select>

        <!-- Municipality filter -->
        <Select
          :model-value="filters.municipality_id?.toString() || 'all'"
          :disabled="isLoadingFilters"
          @update:model-value="(v) => handleFilter('municipality', v as string)"
        >
          <SelectTrigger class="w-full md:w-48">
            <SelectValue placeholder="All Locations" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">All Locations</SelectItem>
            <SelectItem
              v-for="muni in filterOptions?.municipalities"
              :key="muni.id"
              :value="muni.id.toString()"
            >
              {{ muni.label }}
            </SelectItem>
          </SelectContent>
        </Select>
      </div>

      <!-- Results count -->
      <div class="flex items-center justify-between">
        <p class="text-sm text-muted-foreground">
          <template v-if="plantings">
            Showing {{ plantings.data.length }} of {{ plantings.total }} offerings
          </template>
          <template v-else>
            Loading...
          </template>
        </p>
      </div>

      <!-- Offerings grid -->
      <div v-if="!isLoadingOfferings && plantings" class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        <Link
          v-for="offering in plantings.data"
          :key="offering.id"
          :href="dealer.marketplace.show(offering.id).url"
          class="block"
        >
          <OfferingCard :offering="offering" />
        </Link>

        <!-- Empty state -->
        <div
          v-if="plantings.data.length === 0"
          class="col-span-full flex flex-col items-center justify-center rounded-lg border border-dashed py-16 text-center"
        >
          <Filter class="mb-4 size-12 text-muted-foreground/50" />
          <h3 class="mb-1 font-semibold">No offerings found</h3>
          <p class="text-sm text-muted-foreground">
            Try adjusting your search or filters
          </p>
        </div>
      </div>

      <!-- Loading skeletons -->
      <div v-else class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        <Skeleton v-for="i in 8" :key="i" class="h-96 rounded-lg" />
      </div>

      <!-- Pagination -->
      <div
        v-if="plantings && plantings.last_page > 1"
        class="flex items-center justify-between border-t pt-4"
      >
        <Button
          variant="outline"
          size="sm"
          :disabled="plantings.current_page === 1"
          @click="handlePageChange(plantings.current_page - 1)"
        >
          Previous
        </Button>
        <span class="text-sm text-muted-foreground">
          Page {{ plantings.current_page }} of {{ plantings.last_page }}
        </span>
        <Button
          variant="outline"
          size="sm"
          :disabled="plantings.current_page === plantings.last_page"
          @click="handlePageChange(plantings.current_page + 1)"
        >
          Next
        </Button>
      </div>
    </div>
  </AppLayout>
</template>
