<script setup lang="ts">
import { Deferred, Head, router } from '@inertiajs/vue3'
import { Search } from 'lucide-vue-next'
import { ref } from 'vue'
import EmptyState from '@/components/EmptyState.vue'
import MarketplaceCard from '@/components/farmer/MarketplaceCard.vue'
import Heading from '@/components/Heading.vue'
import { Button } from '@/components/ui/button'
import { InputGroup, InputGroupAddon, InputGroupInput } from '@/components/ui/input-group'
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
import type { DealerDemand, DemandFilters } from '@/types/farmer/marketplace'
import type { PaginatedResponse } from '@/types/pagination'
import type { CategoryOption } from '@/types/product/category'

interface Props {
  filters: DemandFilters
  demands?: PaginatedResponse<DealerDemand>
  categoryOptions?: CategoryOption[]
}

const props = defineProps<Props>()

const searchQuery = ref(props.filters.search || '')
const searchDebounce = ref<ReturnType<typeof setTimeout> | null>(null)

function handleSearch() {
  if (searchDebounce.value) clearTimeout(searchDebounce.value)

  searchDebounce.value = setTimeout(() => {
    router.visit(farmer.marketplace.index().url, {
      data: {
        search: searchQuery.value || undefined,
        category_id: props.filters.category_id || undefined,
      },
      preserveState: true,
      preserveScroll: true,
      only: ['demands'],
    })
  }, 300)
}

function handleFilter(type: 'category', value: string) {
  const filters: Record<string, string | undefined> = {
    category_id: undefined,
  }

  if (type === 'category') {
    filters.category_id = value === 'all' ? undefined : value
  }

  router.visit(farmer.marketplace.index().url, {
    data: filters,
    preserveState: true,
    preserveScroll: true,
    only: ['demands'],
  })
}

function handlePageChange(page: number) {
  router.visit(farmer.marketplace.index().url, {
    data: {
      page,
      category_id: props.filters.category_id || undefined,
    },
    preserveScroll: true,
  })
}

const breadcrumbs = [
  { title: 'Farmer', href: farmer.supplies.index().url },
  { title: 'Dealer Posts', href: farmer.marketplace.index().url },
]
</script>

<template>

  <Head title="Dealer Posts" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-col gap-6 p-4 lg:p-6">
      <!-- Header -->
      <Heading title="Dealer Posts"
        description="Browse active purchase requests from dealers and find opportunities." />

      <!-- Filters -->
      <Deferred data="filters">
        <template #fallback>
          <Skeleton />
        </template>

        <div class="md:flex justify-between">
          <InputGroup class="md:w-2/3 lg:w-1/2 xl:w-2/5">
            <InputGroupInput v-model="searchQuery" type="search" @input="handleSearch"
              placeholder="Search vegetables (e.g., Cabbage, Lettuce)..." />
            <InputGroupAddon>
              <Search />
            </InputGroupAddon>
          </InputGroup>

          <Select :model-value="filters.category_id?.toString() || 'all'"
            @update:model-value="(v) => handleFilter('category', v as string)">
            <SelectTrigger class="w-full md:w-48">
              <SelectValue placeholder="All Categories" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">All Categories</SelectItem>
              <SelectItem v-for="category in categoryOptions" :key="category.id" :value="category.id.toString()">
                {{ category.name }}
              </SelectItem>
            </SelectContent>
          </Select>
        </div>
      </Deferred>

      <!-- Results count -->
      <div class="flex items-center justify-between">
        <p class="text-sm text-muted-foreground">
          <Deferred data="demands">
            <template #fallback>Loading...</template>
            Showing {{ demands?.data.length }} of {{ demands?.meta.total }} posts
          </Deferred>
        </p>
      </div>

      <!-- Marketplace grid -->
      <Deferred data="demands">
        <template #fallback>
          <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <Skeleton v-for="i in 6" :key="i" class="h-80 rounded-lg" />
          </div>
        </template>

        <EmptyState v-if="demands?.data.length === 0" title="No Posts Found"
          description="Try adjusting your filters or check back later" />

        <div v-else class="grid gap-6 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
          <MarketplaceCard v-for="demand in demands?.data" :key="demand.id" :demand="demand" />
        </div>
      </Deferred>

      <!-- Pagination -->
      <div v-if="demands && demands.meta.last_page > 1" class="flex items-center justify-between border-t pt-4">
        <Button variant="outline" size="sm" :disabled="demands.meta.current_page === 1"
          @click="handlePageChange(demands.meta.current_page - 1)">
          Previous
        </Button>
        <span class="text-sm text-muted-foreground">
          Page {{ demands.meta.current_page }} of {{ demands.meta.last_page }}
        </span>
        <Button variant="outline" size="sm" :disabled="demands.meta.current_page === demands.meta.last_page"
          @click="handlePageChange(demands.meta.current_page + 1)">
          Next
        </Button>
      </div>
    </div>
  </AppLayout>
</template>
