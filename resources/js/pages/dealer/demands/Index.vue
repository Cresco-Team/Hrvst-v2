<script setup lang="ts">
import { Deferred, Head, router, useForm } from '@inertiajs/vue3'
import { Plus, Sprout } from 'lucide-vue-next'
import { computed, ref } from 'vue'
import ConfirmationDialog from '@/components/dialogs/ConfirmationDialog.vue'
import EmptyState from '@/components/EmptyState.vue'
import DemandForm from '@/components/features/dealer/DemandForm.vue'
import Heading from '@/components/Heading.vue'
import LargeCard from '@/components/shared/cards/LargeCard.vue'
import PostItemCard from '@/components/shared/cards/PostItemCard.vue'
import PostItemEditDialog from '@/components/shared/dialogs/PostItemEditDialog.vue'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs'
import AppLayout from '@/layouts/AppLayout.vue'
import dealer from '@/routes/dealer'
import { index } from '@/routes/dealer/demands'
import {
    archive,
    destroy,
    fulfill,
    update as updatePostItem,
} from '@/routes/dealer/post-items'
import type {
    BreadcrumbItem,
    DealerDemandsProps,
    PostItemSnapshot,
    VarietyOptionsByVegetable,
    VegetableOptionsByCategory,
} from '@/types'

const props = defineProps<DealerDemandsProps>()

// ─── Create demand form ───────────────────────────────────────────────────────

const formOpen = ref(false)

// ─── PostItem actions ─────────────────────────────────────────────────────────

const editItemDialogOpen = ref(false)
const fulfillDialogOpen = ref(false)
const archiveDialogOpen = ref(false)
const deleteDialogOpen = ref(false)

const itemToEdit = ref<PostItemSnapshot | null>(null)
const itemToFulfill = ref<PostItemSnapshot | null>(null)
const itemToArchive = ref<PostItemSnapshot | null>(null)
const itemToDelete = ref<PostItemSnapshot | null>(null)

const fulfillForm = useForm({})
const archiveForm = useForm({})
const deleteForm = useForm({})

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
function openDelete(item: PostItemSnapshot) {
    itemToDelete.value = item
    deleteDialogOpen.value = true
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

function handleDelete() {
    if (!itemToDelete.value) return
    deleteForm.delete(destroy(itemToDelete.value.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            deleteDialogOpen.value = false
            itemToDelete.value = null
        },
    })
}

// ─── Tabs + pagination ────────────────────────────────────────────────────────

const activeTab = computed(() => props.filters.status ?? 'ongoing')

function handleTabChange(value: string | number) {
    router.visit(
        index({ query: { status: value === 'ongoing' ? undefined : value } })
            .url,
        {
            preserveState: true,
            preserveScroll: true,
            only: ['demands', 'filters', 'summary'],
        },
    )
}

function handlePageChange(page: number) {
    router.visit(dealer.demands.index().url, {
        data: { page, status: props.filters.status },
        preserveScroll: true,
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
    { title: 'Dealer', href: dealer.demands.index().url },
    { title: 'Demands', href: dealer.demands.index().url },
]
</script>

<template>
    <Head title="My Demands" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-col gap-6 p-4 lg:p-6">
            <div class="flex items-end justify-between">
                <Heading
                    title="My Demands"
                    description="Post purchase requests for farmers."
                />
                <Button class="gap-2" @click="formOpen = true">
                    <Plus class="size-4" />
                    New Demand
                </Button>
            </div>

            <!-- Summary -->
            <Deferred data="summary">
                <template #fallback>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <Skeleton
                            v-for="i in 3"
                            :key="i"
                            class="h-24 rounded-lg"
                        />
                    </div>
                </template>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <LargeCard
                        title="Ongoing"
                        :value="summary?.total_ongoing"
                        subtext="awaiting supply"
                    />
                    <LargeCard
                        title="Unsettled"
                        :value="summary?.total_unsettled"
                        subtext="closed"
                    />
                    <LargeCard
                        title="Fulfilled"
                        :value="summary?.total_fulfilled"
                        subtext="completed"
                    />
                </div>
            </Deferred>

            <Tabs
                :model-value="activeTab"
                @update:model-value="handleTabChange"
            >
                <TabsList>
                    <TabsTrigger value="ongoing">Ongoing</TabsTrigger>
                    <TabsTrigger value="unsettled">Unsettled</TabsTrigger>
                    <TabsTrigger value="fulfilled">Fulfilled</TabsTrigger>
                </TabsList>
            </Tabs>

            <!-- PostItem grid -->
            <Deferred data="demands">
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
                    v-if="demands?.data.length === 0"
                    title="No Demand Items"
                    description="Post a demand to be picked up by farmers."
                    :icon="Sprout"
                />

                <div
                    v-else
                    class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
                >
                    <PostItemCard
                        v-for="item in demands!.data"
                        :key="item.id"
                        :item="item"
                        mode="demand"
                        :actions="actionsFor(filters.status)"
                        @edit="openEditItem(item)"
                        @fulfill="openFulfill(item)"
                        @archive="openArchive(item)"
                        @delete="openDelete(item)"
                    />
                </div>
            </Deferred>

            <!-- Pagination -->
            <div
                v-if="demands && demands.meta.last_page > 1"
                class="flex items-center justify-between border-t pt-4"
            >
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="demands.meta.current_page === 1"
                    @click="handlePageChange(demands.meta.current_page - 1)"
                >
                    Previous
                </Button>
                <span class="text-sm text-muted-foreground">
                    Page {{ demands.meta.current_page }} of
                    {{ demands.meta.last_page }}
                </span>
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="
                        demands.meta.current_page === demands.meta.last_page
                    "
                    @click="handlePageChange(demands.meta.current_page + 1)"
                >
                    Next
                </Button>
            </div>
        </div>
    </AppLayout>

    <!-- Create demand -->
    <DemandForm
        :open="formOpen"
        :demand="null"
        :vegetable-options="vegetableOptions as VegetableOptionsByCategory"
        :variety-options="varietyOptions as VarietyOptionsByVegetable"
        @update:open="formOpen = $event"
    />

    <!-- PostItem edit -->
    <PostItemEditDialog
        :open="editItemDialogOpen"
        :item="itemToEdit"
        :update-url="itemToEdit ? updatePostItem(itemToEdit.id).url : ''"
        @update:open="editItemDialogOpen = $event"
    />

    <!-- Action dialogs -->
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
        v-model:open="deleteDialogOpen"
        title="Delete Item"
        :description="`Permanently delete ${itemToDelete?.name}? This cannot be undone.`"
        :processing="deleteForm.processing"
        variant="destructive"
        @action="handleDelete"
    />
</template>
