<script setup lang="ts">
import { ref, computed } from 'vue'
import { Head, router, Link } from '@inertiajs/vue3'
import { Search, Filter, ShoppingBag } from 'lucide-vue-next'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import RequestCard from '@/components/farmer/RequestCard.vue'
import AppLayout from '@/layouts/AppLayout.vue'
import Heading from '@/components/Heading.vue'
import type {
  DealerRequest,
  PaginatedResponse,
  RequestFilters,
  CategoryOption,
} from '@/types/announcement'
import farmer from '@/routes/farmer'

interface Props {
  filters: RequestFilters
  requests?: PaginatedResponse<DealerRequest>
  filterOptions?: {
    categories: CategoryOption[]
  }
}

const props = defineProps<Props>()

const isLoadingRequests = computed(() => !props.requests)
const isLoadingFilters = computed(() => !props.filterOptions)

function handleFilter(type: 'category', value: string) {
  const filters: Record<string, string | undefined> = {
    category_id: undefined,
  }

  if (type === 'category') {
    filters.category_id = value === 'all' ? undefined : value
  }

  router.visit(farmer.requests.index().url, {
    data: filters,
    preserveState: true,
    preserveScroll: true,
    only: ['requests'],
  })
}

function handlePageChange(page: number) {
  router.visit(farmer.requests.index().url, {
    data: {
      page,
      category_id: props.filters.category_id || undefined,
    },
    preserveScroll: true,
  })
}

const breadcrumbs = [
  { title: 'Farmer', href: farmer.garden.index().url },
  { title: 'Dealer Requests', href: farmer.requests.index().url },
]
</script>

<template>
  <Head title="Dealer Requests" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-col gap-6 p-4 lg:p-6">
      <!-- Header -->
      <div class="flex items-start justify-between">
        <Heading
          title="Dealer Requests"
          description="Browse active purchase requests from dealers and find opportunities."
        >
          <template #icon>
            <ShoppingBag class="size-8" />
          </template>
        </Heading>
      </div>

      <!-- Filters -->
      <div class="flex flex-col gap-4 md:flex-row">
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
      </div>

      <!-- Results count -->
      <div class="flex items-center justify-between">
        <p class="text-sm text-muted-foreground">
          <template v-if="requests">
            Showing {{ requests.data.length }} of {{ requests.total }} requests
          </template>
          <template v-else>
            Loading...
          </template>
        </p>
      </div>

      <!-- Requests grid -->
      <div v-if="!isLoadingRequests && requests" class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        <Link
          v-for="request in requests.data"
          :key="request.id"
          :href="farmer.requests.show(request.id).url"
          class="block"
        >
          <RequestCard :request="request" />
        </Link>

        <!-- Empty state -->
        <div
          v-if="requests.data.length === 0"
          class="col-span-full flex flex-col items-center justify-center rounded-lg border border-dashed py-16 text-center"
        >
          <Filter class="mb-4 size-12 text-muted-foreground/50" />
          <h3 class="mb-1 font-semibold">No requests found</h3>
          <p class="text-sm text-muted-foreground">
            Try adjusting your filters or check back later
          </p>
        </div>
      </div>

      <!-- Loading skeletons -->
      <div v-else class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        <Skeleton v-for="i in 6" :key="i" class="h-80 rounded-lg" />
      </div>

      <!-- Pagination -->
      <div
        v-if="requests && requests.last_page > 1"
        class="flex items-center justify-between border-t pt-4"
      >
        <Button
          variant="outline"
          size="sm"
          :disabled="requests.current_page === 1"
          @click="handlePageChange(requests.current_page - 1)"
        >
          Previous
        </Button>
        <span class="text-sm text-muted-foreground">
          Page {{ requests.current_page }} of {{ requests.last_page }}
        </span>
        <Button
          variant="outline"
          size="sm"
          :disabled="requests.current_page === requests.last_page"
          @click="handlePageChange(requests.current_page + 1)"
        >
          Next
        </Button>
      </div>
    </div>
  </AppLayout>
</template>
