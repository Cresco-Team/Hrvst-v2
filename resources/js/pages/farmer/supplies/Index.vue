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
import PostItemEditDialog from '@/components/shared/dialogs/PostItemEditDialog.vue'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs'
import AppLayout from '@/layouts/AppLayout.vue'
import farmer from '@/routes/farmer'
import {
    archive,
    destroy,
    fulfill,
    update as updatePostItem,
} from '@/routes/farmer/post-items'
import { destroy as destroySupply, index } from '@/routes/farmer/supplies'
import type {
    BreadcrumbItem,
    PostItemSnapshot,
    FarmerSuppliesProps,
    FarmerSupplyResource,
    VarietyOptionsByVegetable,
    VegetableOptionsByCategory,
} from '@/types'
import PlantItem from '@/components/features/farmer/PlantItem.vue'

const props = defineProps<FarmerSuppliesProps>()

const isGrowing = computed(() => props.filters.status === 'growing')

// ─── Growing post actions ─────────────────────────────────────────────────────

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
    deletePostForm.delete(destroySupply(supplyToDelete.value.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            deletePostDialogOpen.value = false
            supplyToDelete.value = null
        },
    })
}

// ─── PostItem actions ─────────────────────────────────────────────────────────

const editItemDialogOpen = ref(false)
const fulfillDialogOpen = ref(false)
const archiveDialogOpen = ref(false)
const deleteItemDialogOpen = ref(false)
const itemToEdit = ref<PostItemSnapshot | null>(null)
const itemToFulfill = ref<PostItemSnapshot | null>(null)
const itemToArchive = ref<PostItemSnapshot | null>(null)
const itemToDelete = ref<PostItemSnapshot | null>(null)

const fulfillForm = useForm({})
const archiveForm = useForm({})
const deleteItemForm = useForm({})

function openEditItem(item: PostItemSnapshot) {
    itemToEdit.value = item
    editItemDialogOpen.value = true
}
function openFulfill(item: PostItemSnapshot) {
    itemToFulfill.value = item
    fulfillDialogOpen.value = true
}
function openArchive(item: PostItemSnapshot) {
    itemToArchive.value = item
    archiveDialogOpen.value = true
}
function openDeleteItem(item: PostItemSnapshot) {
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
    deleteItemForm.delete(destroy(itemToDelete.value.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            deleteItemDialogOpen.value = false
            itemToDelete.value = null
        },
    })
}

// ─── Tabs + pagination ────────────────────────────────────────────────────────

const DEFAULT_READY_FOR_HARVEST_STATUS = 'ongoing'

const mainTab = computed(() => (isGrowing.value ? 'growing' : 'ready'))

const subTab = computed(() => props.filters.status)

function handleMainTabChange(value: string | number) {
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
            only: [
                'growingPosts',
                'readyForHarvestPosts',
                'filters',
                'summary',
            ],
        },
    )
}

function handleSubTabChange(value: string | number) {
    router.visit(index({ query: { status: value } }).url, {
        preserveState: true,
        preserveScroll: true,
        only: ['readyForHarvestPosts', 'filters'],
    })
}

function handlePageChange(page: number) {
    router.visit(farmer.supplies.index().url, {
        data: { page, status: props.filters.status },
        preserveScroll: true,
        only: [isGrowing.value ? 'growingPosts' : 'readyForHarvestPosts'],
    })
}

function actionsFor(
    status: string,
): Array<'edit' | 'fulfill' | 'archive' | 'delete'> {
    switch (String(status)) {
        case 'ongoing':
            return ['edit', 'delete']
        case 'unsettled':
            return ['edit', 'fulfill', 'delete']
        case 'fulfilled':
            return ['edit', 'archive', 'delete']
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
                <Heading
                    title="My Supplies"
                    description="Track growing crops and schedule deliveries."
                />
                <Button class="gap-2" @click="openCreate">
                    <Plus class="size-4" />
                    New Supply
                </Button>
            </div>

            <Deferred data="summary">
                <template #fallback>
                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                        <Skeleton
                            v-for="i in 4"
                            :key="i"
                            class="h-24 rounded-lg"
                        />
                    </div>
                </template>
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <LargeCard
                        title="Growing"
                        :value="summary?.total_growing"
                        subtext="still not scheduled this month"
                    />
                    <LargeCard
                        title="Ongoing"
                        :value="summary?.total_ongoing"
                        subtext="scheduled this month"
                    />
                    <LargeCard
                        title="Fulfilled"
                        :value="summary?.total_fulfilled"
                        subtext="schedule is complete"
                        :icon="CircleCheckBig"
                    />
                    <LargeCard
                        title="Unsettled"
                        :value="summary?.total_unsettled"
                        subtext="schedule is unsettled"
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
                        <TabsTrigger value="growing">Growing</TabsTrigger>
                        <TabsTrigger value="ready"
                            >Ready for Schedule</TabsTrigger
                        >
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
                            Expired Scheule
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
                            @edit="openEdit(plant)"
                            @harvest="openHarvest(plant)"
                            @delete="openDeletePost(plant)"
                        />
                    </div>

                    <div
                        v-if="growingPosts && growingPosts.meta.last_page > 1"
                        class="flex items-center justify-between border-t pt-4"
                    >
                        <Button
                            variant="outline"
                            size="sm"
                            :disabled="growingPosts.meta.current_page === 1"
                            @click="
                                handlePageChange(
                                    growingPosts.meta.current_page - 1,
                                )
                            "
                            >Previous</Button
                        >
                        <span class="text-sm text-muted-foreground">
                            Page {{ growingPosts.meta.current_page }} of
                            {{ growingPosts.meta.last_page }}
                        </span>
                        <Button
                            variant="outline"
                            size="sm"
                            :disabled="
                                growingPosts.meta.current_page ===
                                growingPosts.meta.last_page
                            "
                            @click="
                                handlePageChange(
                                    growingPosts.meta.current_page + 1,
                                )
                            "
                            >Next</Button
                        >
                    </div>
                </Deferred>
            </template>

            <!-- ── Ongoing / Unsettled / Fulfilled ────────────────────────────── -->
            <template v-else>
                <Deferred data="readyForHarvestPosts">
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
                        v-if="readyForHarvestPosts?.data.length === 0"
                        title="No Items"
                        description="Nothing here yet."
                        :icon="Sprout"
                    />

                    <div
                        v-else
                        class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
                    >
                        <PostItemCard
                            v-for="item in readyForHarvestPosts!.data"
                            :key="item.id"
                            :item="item"
                            mode="supply"
                            :actions="actionsFor(filters.status)"
                            @edit="openEditItem(item)"
                            @fulfill="openFulfill(item)"
                            @archive="openArchive(item)"
                            @delete="openDeleteItem(item)"
                        />
                    </div>

                    <div
                        v-if="
                            readyForHarvestPosts &&
                            readyForHarvestPosts.meta.last_page > 1
                        "
                        class="flex items-center justify-between border-t pt-4"
                    >
                        <Button
                            variant="outline"
                            size="sm"
                            :disabled="
                                readyForHarvestPosts.meta.current_page === 1
                            "
                            @click="
                                handlePageChange(
                                    readyForHarvestPosts.meta.current_page - 1,
                                )
                            "
                            >Previous</Button
                        >
                        <span class="text-sm text-muted-foreground">
                            Page {{ readyForHarvestPosts.meta.current_page }} of
                            {{ readyForHarvestPosts.meta.last_page }}
                        </span>
                        <Button
                            variant="outline"
                            size="sm"
                            :disabled="
                                readyForHarvestPosts.meta.current_page ===
                                readyForHarvestPosts.meta.last_page
                            "
                            @click="
                                handlePageChange(
                                    readyForHarvestPosts.meta.current_page + 1,
                                )
                            "
                            >Next</Button
                        >
                    </div>
                </Deferred>
            </template>
        </div>
    </AppLayout>

    <!-- Growing post forms -->
    <SupplyForm
        :open="formOpen"
        :supply="activeSupply"
        :vegetable-options="
            vegetableOptions as VegetableOptionsByCategory | undefined
        "
        @update:open="formOpen = $event"
    />
    <HarvestForm
        :open="harvestOpen"
        :supply="supplyToHarvest"
        :variety-options="varietyOptions as VarietyOptionsByVegetable"
        @update:open="harvestOpen = $event"
    />

    <!-- PostItem edit -->
    <PostItemEditDialog
        :open="editItemDialogOpen"
        :item="itemToEdit"
        :update-url="itemToEdit ? updatePostItem(itemToEdit.id).url : ''"
        @update:open="editItemDialogOpen = $event"
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
        :description="`Mark ${itemToFulfill?.name} as fulfilled?`"
        :processing="fulfillForm.processing"
        @action="handleFulfill"
    />
    <ConfirmationDialog
        v-model:open="archiveDialogOpen"
        title="Archive Item"
        :description="`Archive ${itemToArchive?.name}?`"
        :processing="archiveForm.processing"
        variant="destructive"
        @action="handleArchive"
    />
    <ConfirmationDialog
        v-model:open="deleteItemDialogOpen"
        title="Delete Item"
        :description="`Permanently delete ${itemToDelete?.name}?`"
        :processing="deleteItemForm.processing"
        variant="destructive"
        @action="handleDeleteItem"
    />
</template>
