<script setup lang="ts">
import { Deferred, Head, router, usePage } from '@inertiajs/vue3'
import { Search } from 'lucide-vue-next'
import type { AcceptableValue } from 'reka-ui'
import { computed, ref } from 'vue'
import EmptyState from '@/components/EmptyState.vue'
import Heading from '@/components/Heading.vue'
import VegetableCatalogCard from '@/components/shared/cards/VegetableCatalogCard.vue'
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
import dealer from '@/routes/dealer'
import farmer from '@/routes/farmer'
import type { BreadcrumbItem } from '@/types'
import type { Paginated } from '@/types/index'
import type { CategoryOption, VegetableResource } from '@/types/resources/product'

interface VegetablesFilters {
	search: string | null
	category_id: number | null
}

interface Props {
	filters: VegetablesFilters
	vegetables?: Paginated<VegetableResource> // Inertia::defer — varieties eager-loaded
	categoryOptions?: CategoryOption[] // Inertia::defer
}

const props = defineProps<Props>()

// ─── State ────────────────────────────────────────────────────────────────────
const searchQuery = ref(props.filters.search ?? '')
let searchDebounce: ReturnType<typeof setTimeout> | null = null

// ─── Breadcrumbs ─────────────────────────────────────────────────────────────
const page = usePage()
const isFarmer = page.props.auth.user.roles.includes('farmer')

const backHref = isFarmer ? farmer.supplies.index().url : dealer.demands.index().url
const indexHref = isFarmer ? farmer.vegetables.index().url : dealer.vegetables.index().url

const breadcrumbs: BreadcrumbItem[] = [
	{ title: isFarmer ? 'Farmer' : 'Dealer', href: backHref },
	{ title: 'Vegetables', href: indexHref },
]

// ─── Derived ─────────────────────────────────────────────────────────────────

// Only render vegetable groups that actually have varieties.
// The backend may return vegetables with an empty varieties array when a search
// matches the vegetable name but none of its child varieties.
const vegetableGroups = computed(() =>
	(props.vegetables?.data ?? []).filter((veg) => (veg.varieties?.length ?? 0) > 0),
)

// Flat variety count across all visible groups — shown in the search result badge.
const totalVarieties = computed(() =>
	vegetableGroups.value.reduce((sum, veg) => sum + (veg.varieties?.length ?? 0), 0),
)

// ─── Handlers ─────────────────────────────────────────────────────────────────
function handleSearch() {
	if (searchDebounce) clearTimeout(searchDebounce)

	searchDebounce = setTimeout(() => {
		router.visit(indexHref, {
			data: {
				search: searchQuery.value || undefined,
				category_id: props.filters.category_id || undefined,
			},
			preserveState: true,
			preserveScroll: true,
			only: ['vegetables'],
		})
	}, 300)
}

function handleCategoryFilter(value: AcceptableValue) {
	const category = value === 'all' || value == null ? undefined : String(value)

	router.visit(indexHref, {
		data: {
			search: props.filters.search || undefined,
			category_id: category,
		},
		preserveState: true,
		preserveScroll: true,
		only: ['vegetables'],
	})
}

function handlePageChange(page: number) {
	router.visit(indexHref, {
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
            <Heading title="Vegetables"
                description="Browse all available varieties and their current market prices." />

            <!-- Filters -->
            <div class="flex flex-wrap gap-3">
                <InputGroup class="w-full sm:max-w-xs">
                    <InputGroupAddon>
                        <Search class="size-4 text-muted-foreground" />
                    </InputGroupAddon>
                    <InputGroupInput v-model="searchQuery" placeholder="Search varieties..."
                        @input="handleSearch" />
                    <InputGroupAddon align="inline-end">
                        {{ totalVarieties }} results
                    </InputGroupAddon>
                </InputGroup>

                <Deferred data="categoryOptions">
                    <template #fallback>
                        <Skeleton class="h-9 w-40" />
                    </template>

                    <Select :model-value="String(filters.category_id ?? 'all')"
                        @update:model-value="handleCategoryFilter">
                        <SelectTrigger class="w-40">
                            <SelectValue placeholder="All categories" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All categories</SelectItem>
                            <SelectItem v-for="cat in categoryOptions" :key="cat.id"
                                :value="String(cat.id)">
                                {{ cat.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </Deferred>
            </div>

            <!-- Grouped card grid -->
            <Deferred data="vegetables">
                <template #fallback>
                    <div class="flex flex-col gap-8">
                        <!-- Skeleton group × 2 -->
                        <div v-for="g in 2" :key="g" class="flex flex-col gap-3">
                            <Skeleton class="h-5 w-32" />
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
                                <Skeleton v-for="i in 4" :key="i" class="aspect-4/3 rounded-xl" />
                            </div>
                        </div>
                    </div>
                </template>

                <EmptyState v-if="!vegetableGroups.length" title="No varieties found"
                    description="Try adjusting your search or category filter." />

                <div v-else class="flex flex-col gap-8">
                    <section v-for="vegetable in vegetableGroups" :key="vegetable.id">
                        <!-- Vegetable group header -->
                        <div class="mb-3 flex items-baseline gap-2">
                            <h2 class="text-base font-semibold">{{ vegetable.name }}</h2>
                            <span class="text-xs text-muted-foreground">
                                {{ vegetable.varieties!.length }}
                                {{ vegetable.varieties!.length === 1 ? 'variety' : 'varieties' }}
                            </span>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
                            <VegetableCatalogCard
                                v-for="variety in vegetable.varieties"
                                :key="variety.id"
                                :variety="variety"
                                :href="`${indexHref}/${variety.id}`"
                            />
                        </div>
                    </section>
                </div>
            </Deferred>

            <!-- Pagination (tracks vegetable pages) -->
            <div v-if="vegetables && vegetables.meta.last_page > 1"
                class="flex items-center justify-between border-t pt-4">
                <Button variant="outline" size="sm"
                    :disabled="vegetables.meta.current_page === 1"
                    @click="handlePageChange(vegetables.meta.current_page - 1)">
                    Previous
                </Button>
                <span class="text-sm text-muted-foreground">
                    Page {{ vegetables.meta.current_page }} of {{ vegetables.meta.last_page }}
                </span>
                <Button variant="outline" size="sm"
                    :disabled="vegetables.meta.current_page === vegetables.meta.last_page"
                    @click="handlePageChange(vegetables.meta.current_page + 1)">
                    Next
                </Button>
            </div>

        </div>
    </AppLayout>
</template>
