<script setup lang="ts">
import { Deferred, Head, router } from '@inertiajs/vue3'
import { Search } from 'lucide-vue-next'
import { ref } from 'vue'
import EmptyState from '@/components/EmptyState.vue'
import Heading from '@/components/Heading.vue'
import PostItemCard from '@/components/shared/cards/PostItemCard.vue'
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
import type { BreadcrumbItem, FarmerMarketplaceProps } from '@/types'

const props = defineProps<FarmerMarketplaceProps>()

const searchQuery = ref(props.filters.search ?? '')
let searchDebounce: ReturnType<typeof setTimeout> | null = null

function handleSearch() {
	if (searchDebounce) clearTimeout(searchDebounce)

	searchDebounce = setTimeout(() => {
		router.visit(farmer.marketplace.index().url, {
			data: {
				search: searchQuery.value || undefined,
				category_id: props.filters.category_id || undefined,
				variety_id: props.filters.variety_id || undefined,
				date_from: props.filters.date_from || undefined,
				date_to: props.filters.date_to || undefined,
			},
			preserveState: true,
			preserveScroll: true,
			only: ['demands'],
		})
	}, 300)
}

function handleCategoryFilter(value: string) {
	router.visit(farmer.marketplace.index().url, {
		data: {
			search: props.filters.search || undefined,
			category_id: value === 'all' ? undefined : value,
			variety_id: undefined,
			date_from: props.filters.date_from || undefined,
			date_to: props.filters.date_to || undefined,
		},
		preserveState: true,
		preserveScroll: true,
		only: ['demands', 'filters'],
	})
}

function handlePageChange(page: number) {
	router.visit(farmer.marketplace.index().url, {
		data: {
			page,
			search: props.filters.search || undefined,
			category_id: props.filters.category_id || undefined,
			variety_id: props.filters.variety_id || undefined,
			date_from: props.filters.date_from || undefined,
			date_to: props.filters.date_to || undefined,
		},
		preserveScroll: true,
	})
}

const breadcrumbs: BreadcrumbItem[] = [
	{ title: 'Farmer', href: farmer.dashboard().url },
	{ title: 'Marketplace', href: farmer.marketplace.index().url },
]
</script>

<template>
	<Head title="Marketplace" />

	<AppLayout :breadcrumbs="breadcrumbs">
		<div class="flex h-full flex-col gap-6 p-4 lg:p-6">

			<Heading title="Marketplace" description="Browse dealer purchase requests for your varieties." />

			<!-- Filters -->
			<Deferred data="categoryOptions">
				<template #fallback>
					<Skeleton class="h-9 w-80" />
				</template>

				<div class="flex flex-wrap gap-3">
					<InputGroup class="w-full sm:max-w-xs">
						<InputGroupAddon>
							<Search class="size-4 text-muted-foreground" />
						</InputGroupAddon>
						<InputGroupInput
							v-model="searchQuery"
							type="search"
							placeholder="Search varieties..."
							@input="handleSearch"
						/>
					</InputGroup>

					<Select
						:model-value="filters.category_id?.toString() ?? 'all'"
						@update:model-value="(v) => handleCategoryFilter(v as string)"
					>
						<SelectTrigger class="w-full sm:w-48">
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
			</Deferred>

			<!-- Demand item grid -->
			<Deferred data="demands">
				<template #fallback>
					<div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
						<Skeleton v-for="i in 8" :key="i" class="h-80 rounded-lg" />
					</div>
				</template>

				<p class="text-sm text-muted-foreground">
					Showing {{ demands?.data.length }} of {{ demands?.meta.total }} requests
				</p>

				<EmptyState
					v-if="demands?.data.length === 0"
					title="No Requests Found"
					description="Try adjusting your filters or check back later."
				/>

				<div v-else class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
					<PostItemCard
						v-for="item in demands!.data"
						:key="item.id"
						:item="item"
						mode="demand"
					/>
				</div>
			</Deferred>

			<!-- Pagination -->
			<div v-if="demands && demands.meta.last_page > 1" class="flex items-center justify-between border-t pt-4">
				<Button
					variant="outline"
					size="sm"
					:disabled="demands.meta.current_page === 1"
					@click="handlePageChange(demands.meta.current_page - 1)"
				>
					Previous
				</Button>
				<span class="text-sm text-muted-foreground">
					Page {{ demands.meta.current_page }} of {{ demands.meta.last_page }}
				</span>
				<Button
					variant="outline"
					size="sm"
					:disabled="demands.meta.current_page === demands.meta.last_page"
					@click="handlePageChange(demands.meta.current_page + 1)"
				>
					Next
				</Button>
			</div>

		</div>
	</AppLayout>
</template>
