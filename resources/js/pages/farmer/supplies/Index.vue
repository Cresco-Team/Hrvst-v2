<script setup lang="ts">
import { Deferred, Head, router, useForm } from '@inertiajs/vue3'
import { CircleCheckBig, Plus, Sprout } from 'lucide-vue-next'
import { computed, ref } from 'vue'
import ConfirmationDialog from '@/components/dialogs/ConfirmationDialog.vue'
import EmptyState from '@/components/EmptyState.vue'
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
} from '@/types'

type PostItem = FarmerSupplyResource | DealerDemandResource

const props = defineProps<FarmerSuppliesProps>()

const formOpen = ref(false)
const activeSupply = ref<FarmerSupplyResource | null>(null)

const archiveDialogOpen = ref(false)
const fulfillDialogOpen = ref(false)
const deleteDialogOpen = ref(false)
const supplyToArchive = ref<FarmerSupplyResource | null>(null)
const supplyToFulfill = ref<FarmerSupplyResource | null>(null)
const supplyToDelete = ref<FarmerSupplyResource | null>(null)

const fulfillForm = useForm({})
const archiveForm = useForm({})
const deleteForm = useForm({})

const activeTab = computed(() => props.filters.status ?? 'Ongoing')

function handleTabChange(value: string | number) {
	router.visit(index({ query: { status: value === 'Ongoing' ? undefined : value } }).url, {
		preserveState: true,
		preserveScroll: true,
		only: ['supplies', 'filters', 'summary'],
	})
}

function openCreate() {
	activeSupply.value = null
	formOpen.value = true
}

function openEdit(supply: PostItem) {
	activeSupply.value = supply as FarmerSupplyResource
	formOpen.value = true
}

function openArchive(supply: PostItem) {
	supplyToArchive.value = supply as FarmerSupplyResource
	archiveDialogOpen.value = true
}

function openFulfill(supply: PostItem) {
	supplyToFulfill.value = supply as FarmerSupplyResource
	fulfillDialogOpen.value = true
}

function openDelete(supply: PostItem) {
	supplyToDelete.value = supply as FarmerSupplyResource
	deleteDialogOpen.value = true
}

function handleArchive() {
	if (!supplyToArchive.value) return
	archiveForm.post(archive(supplyToArchive.value.id).url, {
		preserveScroll: true,
		onSuccess: () => {
			archiveDialogOpen.value = false
			supplyToArchive.value = null
		},
	})
}

function handleFulfill() {
	if (!supplyToFulfill.value) return
	fulfillForm.post(fulfill(supplyToFulfill.value.id).url, {
		preserveScroll: true,
		onSuccess: () => {
			fulfillDialogOpen.value = false
			supplyToFulfill.value = null
		},
	})
}

function handleDelete() {
	if (!supplyToDelete.value) return
	deleteForm.post(destroy(supplyToDelete.value.id).url, {
		method: 'delete',
		preserveScroll: true,
		onSuccess: () => {
			deleteDialogOpen.value = false
			supplyToDelete.value = null
		},
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
				<Heading title="My Supplies" description="Schedule your supplies for delivery." />
				<Button class="gap-2" @click="openCreate">
					<Plus class="size-4" />
					New Supply
				</Button>
			</div>

			<Deferred data="summary">
				<template #fallback>
					<div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
						<Skeleton v-for="i in 3" :key="i" class="h-24 rounded-lg" />
					</div>
				</template>

				<div class="grid md:grid-cols-3 gap-4">
					<LargeCard title="Ongoing Supplies" :value="summary?.total_ongoing" subtext="not yet harvested" />
					<LargeCard title="Archived Supplies" :value="summary?.total_archived"
						subtext="delivered to trading post" />
					<LargeCard title="Fulfilled Supplies" :value="summary?.total_fulfilled"
						subtext="marked as successful" :icon="CircleCheckBig" />
				</div>
			</Deferred>

			<Tabs :model-value="activeTab" @update:model-value="handleTabChange">
				<TabsList>
					<TabsTrigger value="Ongoing">Ongoing</TabsTrigger>
					<TabsTrigger value="Archived">Archived</TabsTrigger>
					<TabsTrigger value="Fulfilled">Fulfilled</TabsTrigger>
				</TabsList>
			</Tabs>

			<Deferred data="supplies">
				<template #fallback>
					<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
						<Skeleton v-for="i in 8" :key="i" class="h-80 rounded-lg" />
					</div>
				</template>

				<EmptyState v-if="supplies?.data.length === 0" title="No Supplies Yet."
					description="Post a supply to be delivered" :icon="Sprout" />

				<div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
					<MyPostCard v-for="supply in supplies!.data" :key="supply.id" :post="supply" @edit="openEdit"
						@archive="openArchive" @fulfill="openFulfill" @delete="openDelete" />
				</div>
			</Deferred>

			<div v-if="supplies && supplies.meta.last_page > 1"
				class="flex items-center justify-between border-t pt-4">
				<Button variant="outline" size="sm" :disabled="supplies.meta.current_page === 1"
					@click="handlePageChange(supplies.meta.current_page - 1)">
					Previous
				</Button>
				<span class="text-sm text-muted-foreground">
					Page {{ supplies.meta.current_page }} of {{ supplies.meta.last_page }}
				</span>
				<Button variant="outline" size="sm" :disabled="supplies.meta.current_page === supplies.meta.last_page"
					@click="handlePageChange(supplies.meta.current_page + 1)">
					Next
				</Button>
			</div>
		</div>
	</AppLayout>

	<SupplyForm :open="formOpen" :supply="activeSupply"
		:vegetable-options="(vegetableOptions as VegetableOptionsByCategory | undefined)"
		@update:open="formOpen = $event" />

	<ConfirmationDialog v-model:open="archiveDialogOpen" title="Archive Supply"
		:description="`Are you sure you want to archive ${supplyToArchive?.vegetable?.name}?`"
		variant="destructive" :processing="archiveForm.processing" @action="handleArchive" />

	<ConfirmationDialog v-model:open="fulfillDialogOpen" title="Fulfill Supply"
		:description="`Are you sure you want to set ${supplyToFulfill?.vegetable?.name} as fulfilled?`"
		:processing="fulfillForm.processing" @action="handleFulfill" />

	<ConfirmationDialog v-model:open="deleteDialogOpen" title="Delete Supply"
		:description="`Are you sure you want to delete ${supplyToDelete?.vegetable?.name}?`"
		:processing="deleteForm.processing" @action="handleDelete" />
</template>
