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
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs'
import AppLayout from '@/layouts/AppLayout.vue'
import farmer from '@/routes/farmer'
import { archive, destroy, fulfill, index } from '@/routes/farmer/supplies'
import type {
	BreadcrumbItem,
	DealerDemandResource,
	FarmerSuppliesProps,
	FarmerSupplyResource,
	VegetableOptionsByCategory,
	VarietyOptionsByVegetable,
} from '@/types'

type PostItem = FarmerSupplyResource | DealerDemandResource

const props = defineProps<FarmerSuppliesProps>()

const formOpen = ref(false)
const activeSupply = ref<FarmerSupplyResource | null>(null)

const harvestOpen = ref(false)
const supplyToHarvest = ref<FarmerSupplyResource | null>(null)

const archiveDialogOpen = ref(false)
const fulfillDialogOpen = ref(false)
const deleteDialogOpen = ref(false)
const supplyToArchive = ref<FarmerSupplyResource | null>(null)
const supplyToFulfill = ref<FarmerSupplyResource | null>(null)
const supplyToDelete = ref<FarmerSupplyResource | null>(null)

const fulfillForm = useForm({})
const archiveForm = useForm({})
const deleteForm = useForm({})

const activeTab = computed(() => props.filters.status ?? 'growing')

function handleTabChange(value: string | number) {
	router.visit(index({ query: { status: value === 'growing' ? undefined : value } }).url, {
		preserveState: true,
		preserveScroll: true,
		only: ['supplies', 'filters', 'summary'],
	})
}

function openCreate() { activeSupply.value = null; formOpen.value = true }
function openEdit(s: PostItem) { activeSupply.value = s as FarmerSupplyResource; formOpen.value = true }
function openHarvest(s: PostItem) { supplyToHarvest.value = s as FarmerSupplyResource; harvestOpen.value = true }
function openArchive(s: PostItem) { supplyToArchive.value = s as FarmerSupplyResource; archiveDialogOpen.value = true }
function openFulfill(s: PostItem) { supplyToFulfill.value = s as FarmerSupplyResource; fulfillDialogOpen.value = true }
function openDelete(s: PostItem) { supplyToDelete.value = s as FarmerSupplyResource; deleteDialogOpen.value = true }

function handleArchive() {
	if (!supplyToArchive.value) return
	archiveForm.post(archive(supplyToArchive.value.id).url, {
		preserveScroll: true,
		onSuccess: () => { archiveDialogOpen.value = false; supplyToArchive.value = null },
	})
}

function handleFulfill() {
	if (!supplyToFulfill.value) return
	fulfillForm.post(fulfill(supplyToFulfill.value.id).url, {
		preserveScroll: true,
		onSuccess: () => { fulfillDialogOpen.value = false; supplyToFulfill.value = null },
	})
}

function handleDelete() {
	if (!supplyToDelete.value) return
	deleteForm.post(destroy(supplyToDelete.value.id).url, {
		method: 'delete',
		preserveScroll: true,
		onSuccess: () => { deleteDialogOpen.value = false; supplyToDelete.value = null },
	})
}

function handlePageChange(page: number) {
	router.visit(farmer.supplies.index().url, {
		data: { page, status: props.filters.status },
		preserveScroll: true,
	})
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
					<LargeCard title="Ongoing" :value="summary?.total_ongoing" subtext="scheduled for delivery" />
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

			<Deferred data="supplies">
				<template #fallback>
					<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
						<Skeleton v-for="i in 8" :key="i" class="h-80 rounded-lg" />
					</div>
				</template>

				<EmptyState
					v-if="supplies?.data.length === 0"
					title="No Supplies Yet."
					description="Register an upcoming harvest to get started."
					:icon="Sprout"
				/>

				<div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
					<MyPostCard
						v-for="supply in supplies!.data"
						:key="supply.id"
						:post="supply"
						@edit="openEdit"
						@harvest="openHarvest"
						@archive="openArchive"
						@fulfill="openFulfill"
						@delete="openDelete"
					/>
				</div>
			</Deferred>

			<div v-if="supplies && supplies.meta.last_page > 1" class="flex items-center justify-between border-t pt-4">
				<Button variant="outline" size="sm" :disabled="supplies.meta.current_page === 1"
					@click="handlePageChange(supplies.meta.current_page - 1)">Previous</Button>
				<span class="text-sm text-muted-foreground">
					Page {{ supplies.meta.current_page }} of {{ supplies.meta.last_page }}
				</span>
				<Button variant="outline" size="sm" :disabled="supplies.meta.current_page === supplies.meta.last_page"
					@click="handlePageChange(supplies.meta.current_page + 1)">Next</Button>
			</div>

		</div>
	</AppLayout>

	<SupplyForm
		:open="formOpen"
		:supply="activeSupply"
		:vegetable-options="(vegetableOptions as VegetableOptionsByCategory | undefined)"
		@update:open="formOpen = $event"
	/>

	<!-- HarvestForm needs varietyOptions, not vegetableOptions — pass from a separate defer if you add it to the controller -->
	<HarvestForm
		:open="harvestOpen"
		:supply="supplyToHarvest"
		:variety-options="(undefined as VarietyOptionsByVegetable | undefined)"
		@update:open="harvestOpen = $event"
	/>

	<ConfirmationDialog v-model:open="archiveDialogOpen" title="Archive Supply"
		:description="`Archive ${supplyToArchive?.vegetable?.name}?`"
		variant="destructive" :processing="archiveForm.processing" @action="handleArchive" />

	<ConfirmationDialog v-model:open="fulfillDialogOpen" title="Fulfill Supply"
		:description="`Mark ${supplyToFulfill?.vegetable?.name} as fulfilled?`"
		:processing="fulfillForm.processing" @action="handleFulfill" />

	<ConfirmationDialog v-model:open="deleteDialogOpen" title="Delete Supply"
		:description="`Permanently delete ${supplyToDelete?.vegetable?.name}?`"
		:processing="deleteForm.processing" @action="handleDelete" />
</template>
