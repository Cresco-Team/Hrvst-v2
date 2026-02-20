<script setup lang="ts">
import { computed } from 'vue'
import { Head, router, Link, Deferred } from '@inertiajs/vue3'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import MarketplaceCard from '@/components/farmer/MarketplaceCard.vue'
import AppLayout from '@/layouts/AppLayout.vue'
import Heading from '@/components/Heading.vue'
import type { CategoryOption } from '@/types/announcement'
import farmer from '@/routes/farmer'
import { PaginatedResponse } from '@/types/pagination'
import EmptyState from '@/components/EmptyState.vue'
import { DealerDemand, DemandFilters } from '@/types/farmer/marketplace'

interface Props {
  filters: DemandFilters
  demands?: PaginatedResponse<DealerDemand>
  categoryOptions?: CategoryOption[]
}

const props = defineProps<Props>()

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
  { title: 'Farmer', href: farmer.garden.index().url },
  { title: 'Dealer Posts', href: farmer.marketplace.index().url },
]
</script>

<template>
  <Head title="Dealer Posts" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-col gap-6 p-4 lg:p-6">
      <!-- Header -->
      <div class="flex items-start justify-between">
        <Heading
          title="Dealer Posts"
          description="Browse active purchase requests from dealers and find opportunities."
        />

        <Deferred data="filters">
          <template #fallback>
            <Skeleton />
          </template>

          <Select
            :model-value="filters.category_id?.toString() || 'all'"
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
        </Deferred>
      </div>

      <!-- Results count -->
      <div class="flex items-center justify-between">
        <p class="text-sm text-muted-foreground">
          <Deferred data="demands">
            <template #fallback>Loading...</template>
            Showing {{ demands?.data.length }} of {{ demands?.total }} posts
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

        <EmptyState 
          v-if="demands?.data.length === 0"
          title="No posts found"
          description="Try adjusting your filters or check back later"
        />

        <div v-else class="grid gap-6 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
          <MarketplaceCard 
            v-for="demand in demands?.data"
            :key="demand.id"
            :demand="demand" 
            :href="farmer.marketplace.show(demand.id).url"
          />
        </div>
       </Deferred>

      <!-- Pagination -->
      <div
        v-if="demands && demands.last_page > 1"
        class="flex items-center justify-between border-t pt-4"
      >
        <Button
          variant="outline"
          size="sm"
          :disabled="demands.current_page === 1"
          @click="handlePageChange(demands.current_page - 1)"
        >
          Previous
        </Button>
        <span class="text-sm text-muted-foreground">
          Page {{ demands.current_page }} of {{ demands.last_page }}
        </span>
        <Button
          variant="outline"
          size="sm"
          :disabled="demands.current_page === demands.last_page"
          @click="handlePageChange(demands.current_page + 1)"
        >
          Next
        </Button>
      </div>
    </div>
  </AppLayout>
</template>
