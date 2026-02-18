<script setup lang="ts">
import { ref, computed, onMounted, toRaw } from 'vue'
import { Head, router, Link, Deferred } from '@inertiajs/vue3'
import { Search, Filter } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Skeleton } from '@/components/ui/skeleton'
import AppLayout from '@/layouts/AppLayout.vue'
import Heading from '@/components/Heading.vue'
import dealer from '@/routes/dealer'
import { PaginatedResponse } from '@/types/pagination'
import { CategoryOption, MarketplaceFilters, Offering } from '@/types/dealer/marketplace'
import { InputGroup, InputGroupAddon, InputGroupInput } from '@/components/ui/input-group'
import MarketplaceCard from '@/components/dealer/MarketplaceCard.vue'

interface Props {
  filters: MarketplaceFilters
  offerings?: PaginatedResponse<Offering>
  filterOptions?: {
    categories: CategoryOption[]
  }
}

const props = defineProps<Props>()

onMounted(() => console.log(props.offerings))

console.log(toRaw(props))

const searchQuery = ref(props.filters.search || '')
const searchDebounce = ref<ReturnType<typeof setTimeout> | null>(null)

const isLoadingOfferings = computed(() => !props.offerings)
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
          description="Browse active farmer offerings and connect with local producers."
        />

      <!-- Filters -->
      <div class="md:flex justify-between">
         <InputGroup class="md:w-2/3 lg:w-1/2 xl:w-2/5">
          <InputGroupInput 
            v-model="searchQuery"
            type="search"
            @input="handleSearch"
            placeholder="Search varieties (e.g., Cabbage, Lettuce)..." 
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
              v-for="category in filterOptions?.categories"
              :key="category.id"
              :value="category.id.toString()"
            >
              {{ category.name }}
            </SelectItem>
          </SelectContent>
        </Select>
      </div>

      <!-- Results count -->
      <Deferred data="offerings">
        <template #fallback>
          <p class="text-sm text-muted-foreground">Loading...</p>

          <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            <Skeleton v-for="i in 8" :key="i" class="h-96 rounded-lg" />
          </div>
        </template>
        
        <p class="text-sm text-muted-foreground">Showing {{ offerings?.data.length }} of {{ offerings?.total }} offerings</p>

        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
          <Link
            v-for="offering in offerings?.data"
            :key="offering.id"
            :href="dealer.marketplace.show(offering.id).url"
            class="block"
          >
            <MarketplaceCard :offering="offering" />
          </Link>
        </div>
      </Deferred>

      <!-- Offerings grid -->
      <div v-if="!isLoadingOfferings && offerings" class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        

        <!-- Empty state -->
        <div
          v-if="offerings.data.length === 0"
          class="col-span-full flex flex-col items-center justify-center rounded-lg border border-dashed py-16 text-center"
        >
          <Filter class="mb-4 size-12 text-muted-foreground/50" />
          <h3 class="mb-1 font-semibold">No offerings found</h3>
          <p class="text-sm text-muted-foreground">
            Try adjusting your search or filters
          </p>
        </div>
      </div>

      <!-- Pagination -->
      <div
        v-if="offerings && offerings.last_page > 1"
        class="flex items-center justify-between border-t pt-4"
      >
        <Button
          variant="outline"
          size="sm"
          :disabled="offerings.current_page === 1"
          @click="handlePageChange(offerings.current_page - 1)"
        >
          Previous
        </Button>
        <span class="text-sm text-muted-foreground">
          Page {{ offerings.current_page }} of {{ offerings.last_page }}
        </span>
        <Button
          variant="outline"
          size="sm"
          :disabled="offerings.current_page === offerings.last_page"
          @click="handlePageChange(offerings.current_page + 1)"
        >
          Next
        </Button>
      </div>
    </div>
  </AppLayout>
</template>
