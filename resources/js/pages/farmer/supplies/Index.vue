<script setup lang="ts">
import { Deferred, Head, router, useForm } from '@inertiajs/vue3'
import { CircleCheckBig, Plus, Sprout } from 'lucide-vue-next'
import { computed, ref } from 'vue'
import ConfirmationDialog from '@/components/dialogs/ConfirmationDialog.vue'
import EmptyState from '@/components/EmptyState.vue'
import HarvestForm from '@/components/features/farmer/HarvestForm.vue'
import SupplyForm from '@/components/features/farmer/SupplyForm.vue'
import Heading from '@/components/Heading.vue'
import LargeCard from '@/components/shared/cards/LargeCard.vue'
import MyPostCard from '@/components/shared/cards/MyPostCard.vue'
import PostItemCard from '@/components/shared/cards/PostItemCard.vue'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs'
import AppLayout from '@/layouts/AppLayout.vue'
import farmer from '@/routes/farmer'
import { archive, destroy, fulfill } from '@/routes/farmer/post-items'
import { destroy as destroySupply, index } from '@/routes/farmer/supplies'
import type {
	BreadcrumbItem,
	DealerPostItemResource,
	FarmerSuppliesProps,
	FarmerSupplyResource,
	VarietyOptionsByVegetable,
	VegetableOptionsByCategory,
} from '@/types'

const props = defineProps<FarmerSuppliesProps>()

const isGrowing = computed(() => props.filters.status === 'growing')

// ─── Growing post actions (Post-level: harvest + delete only) ─────────────────

const formOpen = ref(false)
const activeSupply = ref<FarmerSupplyResource | null>(null)
const harvestOpen = ref(false)
const supplyToHarvest = ref<FarmerSupplyResource | null>(null)
const deletePostDialogOpen = ref(false)
const supplyToDelete = ref<FarmerSupplyResource | null>(null)
const deletePostForm = useForm({})

function openCreate() {
	activeSupply.value = null
	formOpen.value = true
}
function openEdit(post: FarmerSupplyResource) {
	activeSupply.value = post
	formOpen.value = true
}
function openHarvest(post: FarmerSupplyResource) {
	supplyToHarvest.value = post
	harvestOpen.value = true
}
function openDeletePost(post: FarmerSupplyResource) {
	supplyToDelete.value = post
	deletePostDialogOpen.value = true
}

function handleDeletePost() {
	if (!supplyToDelete.value) return
	deletePostForm.post(destroySupply(supplyToDelete.value.id).url, {
		method: 'delete',
		preserveScroll: true,
		onSuccess: () => {
			deletePostDialogOpen.value = false
			supplyToDelete.value = null
		},
	})
}

// ─── PostItem actions (fulfill, archive, delete) ──────────────────────────────

const fulfillDialogOpen = ref(false)
const archiveDialogOpen = ref(false)
const deleteItemDialogOpen = ref(false)
const itemToFulfill = ref<DealerPostItemResource | null>(null)
const itemToArchive = ref<DealerPostItemResource | null>(null)
const itemToDelete = ref<DealerPostItemResource | null>(null)

const fulfillForm = useForm({})
const archiveForm = useForm({})
const deleteItemForm = useForm({})

function openFulfill(item: DealerPostItemResource) {
	itemToFulfill.value = item
	fulfillDialogOpen.value = true
}
function openArchive(item: DealerPostItemResource) {
	itemToArchive.value = item
	archiveDialogOpen.value = true
}
function openDeleteItem(item: DealerPostItemResource) {
	itemToDelete.value = item
	deleteItemDialogOpen.value = true
}

function handleFulfill() {
	if (!itemToFulfill.value) return
	fulfillForm.post(fulfill(itemToFulfill.value.id).url, {
		preserveScroll: true,
		onSuccess: () => {
			fulfillDialogOpen.value = false
			itemToFulfill.value = null
		},
	})
}

function handleArchive() {
	if (!itemToArchive.value) return
	archiveForm.post(archive(itemToArchive.value.id).url, {
		preserveScroll: true,
		onSuccess: () => {
			archiveDialogOpen.value = false
			itemToArchive.value = null
		},
	})
}

function handleDeleteItem() {
	if (!itemToDelete.value) return
	deleteItemForm.post(destroy(itemToDelete.value.id).url, {
		method: 'delete',
		preserveScroll: true,
		onSuccess: () => {
			deleteItemDialogOpen.value = false
			itemToDelete.value = null
		},
	})
}

// ─── Tabs + pagination ────────────────────────────────────────────────────────

const activeTab = computed(() => props.filters.status ?? 'growing')

function handleTabChange(value: string | number) {
	router.visit(index({ query: { status: value === 'growing' ? undefined : value } }).url, {
		preserveState: true,
		preserveScroll: true,
		only: ['growingPosts', 'harvestedItems', 'filters', 'summary'],
	})
}

function handlePageChange(page: number) {
	router.visit(farmer.supplies.index().url, {
		data: { page, status: props.filters.status },
		preserveScroll: true,
		only: [isGrowing.value ? 'growingPosts' : 'harvestedItems'],
	})
}

function actionsFor(status: string): Array<'fulfill' | 'archive' | 'delete'> {
	switch (String(status)) {
		case 'ongoing':
			return ['fulfill', 'archive', 'delete']
		case 'archived':
			return ['fulfill', 'delete']
		case 'fulfilled':
			return ['delete']
		default:
			return []
	}
}

const breadcrumbs: BreadcrumbItem[] = [
	{ title: 'Farmer', href: farmer.dashboard().url },
	{ title: 'Supplies', href: farmer.supplies.index().url },
]
</script>

<template>
	<Head title="My Supplies" />

	<AppLayout :breadcrumbs="breadcrumbs">
		<div class="flex h-full flex-col gap-6 p-4 lg:p-6">

			<div class="flex items-end justify-between">
				<Heading title="My Supplies" description="Track growing crops and schedule deliveries." />
				<Button class="gap-2" @click="openCreate">
					<Plus class="size-4" />
					New Supply
				</Button>
			</div>

			<Deferred data="summary">
				<template #fallback>
					<div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
						<Skeleton v-for="i in 4" :key="i" class="h-24 rounded-lg" />
					</div>
				</template>
				<div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
					<LargeCard title="Growing" :value="summary?.total_growing" subtext="pre-harvest" />
					<LargeCard title="Ongoing" :value="summary?.total_ongoing" subtext="scheduled" />
					<LargeCard title="Fulfilled" :value="summary?.total_fulfilled" subtext="completed" :icon="CircleCheckBig" />
					<LargeCard title="Archived" :value="summary?.total_archived" subtext="closed" />
				</div>
			</Deferred>

			<Tabs :model-value="activeTab" @update:model-value="handleTabChange">
				<TabsList>
					<TabsTrigger value="growing">Growing</TabsTrigger>
					<TabsTrigger value="ongoing">Ongoing</TabsTrigger>
					<TabsTrigger value="archived">Archived</TabsTrigger>
					<TabsTrigger value="fulfilled">Fulfilled</TabsTrigger>
				</TabsList>
			</Tabs>

			<!-- ── Growing: Post-level cards (harvest + delete) ───────────────── -->
			<template v-if="isGrowing">
				<Deferred data="growingPosts">
					<template #fallback>
						<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
							<Skeleton v-for="i in 8" :key="i" class="h-80 rounded-lg" />
						</div>
					</template>

					<EmptyState
						v-if="growingPosts?.data.length === 0"
						title="No Growing Supplies"
						description="Register an upcoming harvest to get started."
						:icon="Sprout"
					/>

					<div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
						<MyPostCard
							v-for="post in growingPosts!.data"
							:key="post.id"
							:post="post"
							@edit="openEdit(post)"
							@harvest="openHarvest(post)"
							@delete="openDeletePost(post)"
						/>
					</div>

					<div v-if="growingPosts && growingPosts.meta.last_page > 1"
						class="flex items-center justify-between border-t pt-4">
						<Button variant="outline" size="sm"
							:disabled="growingPosts.meta.current_page === 1"
							@click="handlePageChange(growingPosts.meta.current_page - 1)">Previous</Button>
						<span class="text-sm text-muted-foreground">
							Page {{ growingPosts.meta.current_page }} of {{ growingPosts.meta.last_page }}
						</span>
						<Button variant="outline" size="sm"
							:disabled="growingPosts.meta.current_page === growingPosts.meta.last_page"
							@click="handlePageChange(growingPosts.meta.current_page + 1)">Next</Button>
					</div>
				</Deferred>
			</template>

			<!-- ── Ongoing / Archived / Fulfilled: PostItem cards ────────────── -->
			<template v-else>
				<Deferred data="harvestedItems">
					<template #fallback>
						<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
							<Skeleton v-for="i in 8" :key="i" class="h-80 rounded-lg" />
						</div>
					</template>

					<EmptyState
						v-if="harvestedItems?.data.length === 0"
						title="No Items"
						description="Nothing here yet."
						:icon="Sprout"
					/>

					<div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
						<PostItemCard
							v-for="item in harvestedItems!.data"
							:key="item.id"
							:item="item"
							mode="supply"
							:actions="actionsFor(filters.status)"
							@fulfill="openFulfill(item)"
							@archive="openArchive(item)"
							@delete="openDeleteItem(item)"
						/>
					</div>

					<div v-if="harvestedItems && harvestedItems.meta.last_page > 1"
						class="flex items-center justify-between border-t pt-4">
						<Button variant="outline" size="sm"
							:disabled="harvestedItems.meta.current_page === 1"
							@click="handlePageChange(harvestedItems.meta.current_page - 1)">Previous</Button>
						<span class="text-sm text-muted-foreground">
							Page {{ harvestedItems.meta.current_page }} of {{ harvestedItems.meta.last_page }}
						</span>
						<Button variant="outline" size="sm"
							:disabled="harvestedItems.meta.current_page === harvestedItems.meta.last_page"
							@click="handlePageChange(harvestedItems.meta.current_page + 1)">Next</Button>
					</div>
				</Deferred>
			</template>

		</div>
	</AppLayout>

	<!-- Growing post forms -->
	<SupplyForm
		:open="formOpen"
		:supply="activeSupply"
		:vegetable-options="(vegetableOptions as VegetableOptionsByCategory | undefined)"
		@update:open="formOpen = $event"
	/>
	<HarvestForm
		:open="harvestOpen"
		:supply="supplyToHarvest"
		:variety-options="(varietyOptions as VarietyOptionsByVegetable)"
		@update:open="harvestOpen = $event"
	/>

	<!-- Growing post delete -->
	<ConfirmationDialog
		v-model:open="deletePostDialogOpen"
		title="Delete Supply"
		:description="`Permanently delete ${supplyToDelete?.vegetable?.name} supply?`"
		:processing="deletePostForm.processing"
		variant="destructive"
		@action="handleDeletePost"
	/>

	<!-- PostItem action dialogs -->
	<ConfirmationDialog
		v-model:open="fulfillDialogOpen"
		title="Fulfill Item"
		:description="`Mark ${itemToFulfill?.vegetable_name} ${itemToFulfill?.variety_name} as fulfilled?`"
		:processing="fulfillForm.processing"
		@action="handleFulfill"
	/>
	<ConfirmationDialog
		v-model:open="archiveDialogOpen"
		title="Archive Item"
		:description="`Archive ${itemToArchive?.vegetable_name} ${itemToArchive?.variety_name}?`"
		:processing="archiveForm.processing"
		variant="destructive"
		@action="handleArchive"
	/>
	<ConfirmationDialog
		v-model:open="deleteItemDialogOpen"
		title="Delete Item"
		:description="`Permanently delete ${itemToDelete?.vegetable_name} ${itemToDelete?.variety_name}?`"
		:processing="deleteItemForm.processing"
		variant="destructive"
		@action="handleDeleteItem"
	/>
</template>
