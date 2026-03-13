<script setup lang="ts">
import { Head, router, Link, Deferred } from '@inertiajs/vue3'
import { Search } from 'lucide-vue-next'
import { ref, computed } from 'vue'
import MarketplaceCard from '@/components/dealer/MarketplaceCard.vue'
import EmptyState from '@/components/EmptyState.vue'
import Heading from '@/components/Heading.vue'
import { Button } from '@/components/ui/button'
import { InputGroup, InputGroupAddon, InputGroupInput } from '@/components/ui/input-group'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Skeleton } from '@/components/ui/skeleton'
import AppLayout from '@/layouts/AppLayout.vue'
import dealer from '@/routes/dealer'
import type { MarketplaceFilters, Supply } from '@/types/dealer/marketplace'
import type { PaginatedResponse } from '@/types/pagination'
import type { CategoryOption } from '@/types/product/category'

interface Props {
  filters: MarketplaceFilters
  supplies?: PaginatedResponse<Supply>
  categoryOptions?: CategoryOption[]
}

const props = defineProps<Props>()

const searchQuery = ref(props.filters.search || '')
const searchDebounce = ref<ReturnType<typeof setTimeout> | null>(null)

const isLoadingFilters = computed(() => !props.categoryOptions)

function handleSearch() {
  if (searchDebounce.value) clearTimeout(searchDebounce.value)

  searchDebounce.value = setTimeout(() => {
    router.visit(dealer.marketplace.index().url, {
      data: {
        search: searchQuery.value || undefined,
        category_id: props.filters.category_id || undefined,
      },
      preserveState: true,
      preserveScroll: true,
      only: ['supplies'],
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
  } else {
    filters.category_id = props.filters.category_id?.toString()
  }

  router.visit(dealer.marketplace.index().url, {
    data: filters,
    preserveState: true,
    preserveScroll: true,
    only: ['supplies'],
  })
}

function handlePageChange(page: number) {
  router.visit(dealer.marketplace.index().url, {
    data: {
      page,
      search: searchQuery.value || undefined,
      category_id: props.filters.category_id || undefined,
    },
    preserveScroll: true,
  })
}

const breadcrumbs = [
  { title: 'Dealer', href: dealer.marketplace.index().url },
  { title: 'Marketplace', href: dealer.marketplace.index().url },
]
</script>

<template>
  <Head title="Marketplace" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-col gap-6 p-4 lg:p-6">
      <!-- Header -->
      <Heading
        title="Marketplace"
        description="Browse available farmer posts and connect with local producers."
      />

      <!-- Filters -->
      <div class="md:flex justify-between">
         <InputGroup class="md:w-2/3 lg:w-1/2 xl:w-2/5">
          <InputGroupInput 
            v-model="searchQuery"
            type="search"
            @input="handleSearch"
            placeholder="Search vegetables (e.g., Cabbage, Lettuce)..." 
          />
          <InputGroupAddon>
            <Search />
          </InputGroupAddon>
        </InputGroup>
        
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
              v-for="category in categoryOptions"
              :key="category.id"
              :value="category.id.toString()"
            >
              {{ category.name }}
            </SelectItem>
          </SelectContent>
        </Select>
      </div>

      <!-- Results count -->
      <Deferred data="supplies">
        <template #fallback>
          <p class="text-sm text-muted-foreground">Loading...</p>

          <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            <Skeleton v-for="i in 8" :key="i" class="h-96 rounded-lg" />
          </div>
        </template>
        
        <p class="text-sm text-muted-foreground">Showing {{ supplies?.data.length }} of {{ supplies?.total }} offerings</p>

        <EmptyState 
          v-if="supplies?.data.length === 0"
          title="No Offerings Found"
          description="Try adjusting your search filters"
          :icon="Search"
        />

        <div v-else class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
          <Link
            v-for="supply in supplies?.data"
            :key="supply.id"
            :href="dealer.marketplace.show(supply.id).url"
            class="block"
          >
            <MarketplaceCard :supply="supply" />
          </Link>
        </div>
      </Deferred>

      <!-- Pagination -->
      <div
        v-if="supplies && supplies.last_page > 1"
        class="flex items-center justify-between border-t pt-4"
      >
        <Button
          variant="outline"
          size="sm"
          :disabled="supplies.current_page === 1"
          @click="handlePageChange(supplies.current_page - 1)"
        >
          Previous
        </Button>
        <span class="text-sm text-muted-foreground">
          Page {{ supplies.current_page }} of {{ supplies.last_page }}
        </span>
        <Button
          variant="outline"
          size="sm"
          :disabled="supplies.current_page === supplies.last_page"
          @click="handlePageChange(supplies.current_page + 1)"
        >
          Next
        </Button>
      </div>
    </div>
  </AppLayout>
</template>
