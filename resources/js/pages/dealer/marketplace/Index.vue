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
import dealer from '@/routes/dealer'
import type { BreadcrumbItem, DealerMarketplaceProps } from '@/types'

const props = defineProps<DealerMarketplaceProps>()

const searchQuery = ref(props.filters.search ?? '')
let searchDebounce: ReturnType<typeof setTimeout> | null = null

function handleSearch() {
	if (searchDebounce) clearTimeout(searchDebounce)

	searchDebounce = setTimeout(() => {
		router.visit(dealer.marketplace.index().url, {
			data: {
				search: searchQuery.value || undefined,
				category_id: props.filters.category_id || undefined,
				variety_id: props.filters.variety_id || undefined,
				municipality_id: props.filters.municipality_id || undefined,
			},
			preserveState: true,
			preserveScroll: true,
			only: ['supplies'],
		})
	}, 300)
}

function handleCategoryFilter(value: string) {
	router.visit(dealer.marketplace.index().url, {
		data: {
			search: props.filters.search || undefined,
			category_id: value === 'all' ? undefined : value,
			variety_id: undefined,
			municipality_id: props.filters.municipality_id || undefined,
		},
		preserveState: true,
		preserveScroll: true,
		only: ['supplies', 'filters'],
	})
}

function handlePageChange(page: number) {
	router.visit(dealer.marketplace.index().url, {
		data: {
			page,
			search: props.filters.search || undefined,
			category_id: props.filters.category_id || undefined,
			variety_id: props.filters.variety_id || undefined,
			municipality_id: props.filters.municipality_id || undefined,
		},
		preserveScroll: true,
	})
}

const breadcrumbs: BreadcrumbItem[] = [
	{ title: 'Dealer', href: dealer.demands.index().url },
	{ title: 'Marketplace', href: dealer.marketplace.index().url },
]
</script>

<template>
	<Head title="Marketplace" />

	<AppLayout :breadcrumbs="breadcrumbs">
		<div class="flex h-full flex-col gap-6 p-4 lg:p-6">

			<Heading title="Marketplace" description="Browse available harvest lots from farmers." />

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

			<!-- Post item grid -->
			<Deferred data="supplies">
				<template #fallback>
					<div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
						<Skeleton v-for="i in 8" :key="i" class="h-80 rounded-lg" />
					</div>
				</template>

				<p class="text-sm text-muted-foreground">
					Showing {{ supplies?.data.length }} of {{ supplies?.meta.total }} lots
				</p>

				<EmptyState
					v-if="supplies?.data.length === 0"
					title="No Lots Available"
					description="Try adjusting your filters or check back later."
				/>

				<div v-else class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
					<PostItemCard
						v-for="item in supplies!.data"
						:key="item.id"
						:item="item"
					/>
				</div>
			</Deferred>

			<!-- Pagination -->
			<div v-if="supplies && supplies.meta.last_page > 1" class="flex items-center justify-between border-t pt-4">
				<Button
					variant="outline"
					size="sm"
					:disabled="supplies.meta.current_page === 1"
					@click="handlePageChange(supplies.meta.current_page - 1)"
				>
					Previous
				</Button>
				<span class="text-sm text-muted-foreground">
					Page {{ supplies.meta.current_page }} of {{ supplies.meta.last_page }}
				</span>
				<Button
					variant="outline"
					size="sm"
					:disabled="supplies.meta.current_page === supplies.meta.last_page"
					@click="handlePageChange(supplies.meta.current_page + 1)"
				>
					Next
				</Button>
			</div>

		</div>
	</AppLayout>
</template>
