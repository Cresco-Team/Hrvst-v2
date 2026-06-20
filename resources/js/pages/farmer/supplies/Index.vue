<script setup lang="ts">
import { Deferred, Head, router, useForm } from '@inertiajs/vue3'
import { Plus, Sprout } from 'lucide-vue-next'
import { computed, ref } from 'vue'
import ConfirmationDialog from '@/components/dialogs/ConfirmationDialog.vue'
import EmptyState from '@/components/EmptyState.vue'
import HarvestForm from '@/components/features/farmer/HarvestForm.vue'
import PlantForm from '@/components/features/farmer/PlantForm.vue'
import SupplyForm from '@/components/features/farmer/SupplyForm.vue'
import Heading from '@/components/Heading.vue'
import LargeCard from '@/components/shared/cards/LargeCard.vue'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs'
import AppLayout from '@/layouts/AppLayout.vue'
import farmer from '@/routes/farmer'
import { destroy as destroySupply, index } from '@/routes/farmer/supplies'
import type {
    BreadcrumbItem,
    FarmerSuppliesProps,
    FarmerSupplyDataFixed,
    VarietyOptionsByVegetable,
    VegetableOptionsByCategory,
} from '@/types'
import PlantItem from '@/components/features/farmer/PlantItem.vue'
import SupplyCard from '@/components/features/farmer/SupplyCard.vue'

const props = defineProps<FarmerSuppliesProps>()

const isGrowing = computed(() => props.filters.status === 'growing')

// ─── Growing post actions ─────────────────────────────────────────────────────

const plantFormOpen = ref(false)
const activeGrowingSupply = ref<FarmerSupplyDataFixed | null>(null)
const harvestOpen = ref(false)
const supplyToHarvest = ref<FarmerSupplyDataFixed | null>(null)

function openCreate() {
    activeGrowingSupply.value = null
    plantFormOpen.value = true
}

function openEditGrowing(post: FarmerSupplyDataFixed) {
    activeGrowingSupply.value = post
    plantFormOpen.value = true
}

function openHarvest(post: FarmerSupplyDataFixed) {
    supplyToHarvest.value = post
    harvestOpen.value = true
}

// ─── Ready supply actions ─────────────────────────────────────────────────────

const supplyFormOpen = ref(false)
const activeReadySupply = ref<FarmerSupplyDataFixed | null>(null)

function openEditSupply(supply: FarmerSupplyDataFixed) {
    activeReadySupply.value = supply
    supplyFormOpen.value = true
}

// ─── Shared post deletion ─────────────────────────────────────────────────────

const deletePostDialogOpen = ref(false)
const supplyToDelete = ref<FarmerSupplyDataFixed | null>(null)
const deletePostForm = useForm({})

function openDeletePost(post: FarmerSupplyDataFixed) {
    supplyToDelete.value = post
    deletePostDialogOpen.value = true
}

function handleDeletePost() {
    if (!supplyToDelete.value) return
    deletePostForm.delete(destroySupply(supplyToDelete.value.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            deletePostDialogOpen.value = false
            supplyToDelete.value = null
        },
    })
}

// ─── Tabs + pagination ────────────────────────────────────────────────────────

const DEFAULT_READY_FOR_HARVEST_STATUS = 'ongoing'

const mainTab = computed(() => (isGrowing.value ? 'growing' : 'ready'))
const subTab = computed(() => props.filters.status)

function handleMainTabChange(value: string | number) {
    if (value === mainTab.value) return
    router.visit(
        index({
            query: {
                status:
                    value === 'growing'
                        ? undefined
                        : DEFAULT_READY_FOR_HARVEST_STATUS,
            },
        }).url,
        {
            preserveState: true,
            preserveScroll: true,
            only: ['growingPosts', 'supplies', 'filters', 'summary'],
        },
    )
}

function handleSubTabChange(value: string | number) {
    router.visit(index({ query: { status: value } }).url, {
        preserveState: true,
        preserveScroll: true,
        only: ['supplies', 'filters'],
    })
}

function handlePageChange(page: number) {
    router.visit(farmer.supplies.index().url, {
        data: { page, status: props.filters.status },
        preserveScroll: true,
        only: [isGrowing.value ? 'growingPosts' : 'supplies'],
    })
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Farmer', href: farmer.dashboard().url },
    { title: 'Supplies', href: farmer.supplies.index().url },
]
</script>

<template>
    <Head title="Supplies" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-col gap-6 p-4 lg:p-6">
            <div class="flex items-end justify-between">
                <Heading
                    title="My Plants & Supplies"
                    description="Track growing plants and schedule supply deliveries."
                />
                <Button class="gap-2" @click="openCreate">
                    <Plus class="size-4" />
                    New Plant
                </Button>
            </div>

            <Deferred data="summary">
                <template #fallback>
                    <div class="grid gap-44 sm:grid-cols-2">
                        <Skeleton
                            v-for="i in 2"
                            :key="i"
                            class="h-24 rounded-lg"
                        />
                    </div>
                </template>
                <div class="grid gap-4 sm:grid-cols-2">
                    <LargeCard
                        title="Total Plants"
                        :value="summary?.total_growing"
                        subtext="still growing"
                    />
                    <LargeCard
                        title="Scheduled Supplies"
                        :value="summary?.total_ongoing"
                        subtext="scheduled this month"
                    />
                </div>
            </Deferred>

            <!-- ── Tier 1: Post status (Growing vs Ready for Harvest) ────────────────── -->
            <div class="flex flex-col gap-3">
                <Tabs
                    :model-value="mainTab"
                    @update:model-value="handleMainTabChange"
                >
                    <TabsList>
                        <TabsTrigger value="growing">Plants</TabsTrigger>
                        <TabsTrigger value="ready">Supplies</TabsTrigger>
                    </TabsList>
                </Tabs>

                <!-- ── Tier 2: PostItem status (only once ready for harvest) ──────────── -->
                <Tabs
                    v-if="!isGrowing"
                    :model-value="subTab"
                    @update:model-value="handleSubTabChange"
                >
                    <TabsList class="h-8 bg-transparent p-0">
                        <TabsTrigger value="ongoing" class="text-xs">
                            Not yet Scheduled
                        </TabsTrigger>
                        <TabsTrigger value="unsettled" class="text-xs">
                            Expired Schedule
                        </TabsTrigger>
                        <TabsTrigger value="fulfilled" class="text-xs">
                            Fulfilled Schedule
                        </TabsTrigger>
                    </TabsList>
                </Tabs>
            </div>

            <!-- ── Growing ────────────────────────────────────────────────────── -->
            <template v-if="isGrowing">
                <Deferred data="growingPosts">
                    <template #fallback>
                        <div
                            class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
                        >
                            <Skeleton
                                v-for="i in 8"
                                :key="i"
                                class="h-80 rounded-lg"
                            />
                        </div>
                    </template>

                    <EmptyState
                        v-if="growingPosts?.data.length === 0"
                        title="No Growing Supplies"
                        description="Register an upcoming schedule to get started."
                        :icon="Sprout"
                    />

                    <div v-else class="grid gap-4 sm:grid-cols-3">
                        <PlantItem
                            v-for="plant in growingPosts!.data"
                            :key="plant.id"
                            :plant="plant"
                            @edit="openEditGrowing(plant)"
                            @harvest="openHarvest(plant)"
                            @delete="openDeletePost(plant)"
                        />
                    </div>

                    <div
                        v-if="growingPosts && growingPosts.last_page > 1"
                        class="flex items-center justify-between border-t pt-4"
                    >
                        <Button
                            variant="outline"
                            size="sm"
                            :disabled="growingPosts.current_page === 1"
                            @click="
                                handlePageChange(growingPosts.current_page - 1)
                            "
                            >Previous</Button
                        >
                        <span class="text-sm text-muted-foreground">
                            Page {{ growingPosts.current_page }} of
                            {{ growingPosts.last_page }}
                        </span>
                        <Button
                            variant="outline"
                            size="sm"
                            :disabled="
                                growingPosts.current_page ===
                                growingPosts.last_page
                            "
                            @click="
                                handlePageChange(growingPosts.current_page + 1)
                            "
                            >Next</Button
                        >
                    </div>
                </Deferred>
            </template>

            <!-- ── Ongoing / Unsettled / Fulfilled ────────────────────────────── -->
            <template v-else>
                <Deferred data="supplies">
                    <template #fallback>
                        <div
                            class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
                        >
                            <Skeleton
                                v-for="i in 8"
                                :key="i"
                                class="h-80 rounded-lg"
                            />
                        </div>
                    </template>

                    <EmptyState
                        v-if="supplies?.data.length === 0"
                        title="No Items"
                        description="Nothing here yet."
                        :icon="Sprout"
                    />

                    <div v-else class="grid gap-4 sm:grid-cols-3">
                        <SupplyCard
                            v-for="supply in supplies!.data"
                            :key="supply.id"
                            :supply="supply"
                            @edit="openEditSupply(supply)"
                            @delete="openDeletePost(supply)"
                        />
                    </div>

                    <div
                        v-if="supplies && supplies.last_page > 1"
                        class="flex items-center justify-between border-t pt-4"
                    >
                        <Button
                            variant="outline"
                            size="sm"
                            :disabled="supplies.current_page === 1"
                            @click="handlePageChange(supplies.current_page - 1)"
                            >Previous</Button
                        >
                        <span class="text-sm text-muted-foreground">
                            Page {{ supplies.current_page }} of
                            {{ supplies.last_page }}
                        </span>
                        <Button
                            variant="outline"
                            size="sm"
                            :disabled="
                                supplies.current_page === supplies.last_page
                            "
                            @click="handlePageChange(supplies.current_page + 1)"
                            >Next</Button
                        >
                    </div>
                </Deferred>
            </template>
        </div>
    </AppLayout>

    <!-- Growing post forms -->
    <PlantForm
        :open="plantFormOpen"
        :supply="activeGrowingSupply"
        :vegetable-options="
            vegetableOptions as VegetableOptionsByCategory | undefined
        "
        @update:open="plantFormOpen = $event"
    />
    <HarvestForm
        :open="harvestOpen"
        :supply="supplyToHarvest"
        :variety-options="varietyOptions as VarietyOptionsByVegetable"
        @update:open="harvestOpen = $event"
    />

    <!-- Ready supply edit form -->
    <SupplyForm
        :open="supplyFormOpen"
        :supply="activeReadySupply"
        :variety-options="varietyOptions as VarietyOptionsByVegetable"
        @update:open="supplyFormOpen = $event"
    />

    <!-- Post delete (shared for both growing and ready) -->
    <ConfirmationDialog
        v-model:open="deletePostDialogOpen"
        title="Delete Supply"
        :description="`Permanently delete ${supplyToDelete?.vegetable?.name} supply?`"
        :processing="deletePostForm.processing"
        variant="destructive"
        @action="handleDeletePost"
    />
</template>
