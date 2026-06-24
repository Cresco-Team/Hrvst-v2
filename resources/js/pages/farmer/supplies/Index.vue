<script setup lang="ts">
import { Deferred, Head, router, useForm } from '@inertiajs/vue3'
import { Package, Plus } from 'lucide-vue-next'
import { computed, ref } from 'vue'
import ConfirmationDialog from '@/components/dialogs/ConfirmationDialog.vue'
import EmptyState from '@/components/EmptyState.vue'
import SupplyCard from '@/components/features/farmer/SupplyCard.vue'
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

const props = defineProps<FarmerSuppliesProps>()

// ─── Supply form (create + edit) ─────────────────────────────────────────────

const supplyFormOpen = ref(false)
const activeSupply = ref<FarmerSupplyDataFixed | null>(null)

function openCreate() {
    activeSupply.value = null
    supplyFormOpen.value = true
}

function openEdit(supply: FarmerSupplyDataFixed) {
    activeSupply.value = supply
    supplyFormOpen.value = true
}

// ─── Delete ───────────────────────────────────────────────────────────────────

const deleteDialogOpen = ref(false)
const supplyToDelete = ref<FarmerSupplyDataFixed | null>(null)
const deleteForm = useForm({})

function openDelete(supply: FarmerSupplyDataFixed) {
    supplyToDelete.value = supply
    deleteDialogOpen.value = true
}

function handleDelete() {
    if (!supplyToDelete.value) return
    deleteForm.delete(destroySupply(supplyToDelete.value.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            deleteDialogOpen.value = false
            supplyToDelete.value = null
        },
    })
}

// ─── Status tabs + pagination ─────────────────────────────────────────────────

const currentStatus = computed(() => props.filters.status)

function handleStatusChange(value: string | number) {
    router.visit(index({ query: { status: value } }).url, {
        preserveState: true,
        preserveScroll: true,
        only: ['supplies', 'filters'],
    })
}

function handlePageChange(page: number) {
    router.visit(index({ query: { status: props.filters.status, page } }).url, {
        preserveScroll: true,
        only: ['supplies'],
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
                    title="My Supplies"
                    description="Post and manage your vegetable supply schedules."
                />
                <Button class="gap-2" @click="openCreate">
                    <Plus class="size-4" />
                    New Supply
                </Button>
            </div>

            <!-- ── Summary ────────────────────────────────────────────── -->
            <Deferred data="summary">
                <template #fallback>
                    <div class="grid gap-4 sm:grid-cols-3">
                        <Skeleton
                            v-for="i in 3"
                            :key="i"
                            class="h-24 rounded-lg"
                        />
                    </div>
                </template>
                <div class="grid gap-4 sm:grid-cols-3">
                    <LargeCard
                        title="Ongoing"
                        :value="summary?.total_ongoing"
                        subtext="awaiting fulfillment"
                    />
                    <LargeCard
                        title="Fulfilled"
                        :value="summary?.total_fulfilled"
                        subtext="completed"
                    />
                    <LargeCard
                        title="Expired"
                        :value="summary?.total_expired"
                        subtext="expired without fulfillment"
                    />
                </div>
            </Deferred>

            <!-- ── Status tabs ────────────────────────────────────────── -->
            <Tabs
                :model-value="currentStatus"
                @update:model-value="handleStatusChange"
            >
                <TabsList>
                    <TabsTrigger value="ongoing">Ongoing</TabsTrigger>
                    <TabsTrigger value="expired">Expired</TabsTrigger>
                    <TabsTrigger value="fulfilled">Fulfilled</TabsTrigger>
                </TabsList>
            </Tabs>

            <!-- ── Supply list ────────────────────────────────────────── -->
            <Deferred data="supplies">
                <template #fallback>
                    <div
                        class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
                    >
                        <Skeleton
                            v-for="i in 8"
                            :key="i"
                            class="h-36 rounded-lg"
                        />
                    </div>
                </template>

                <EmptyState
                    v-if="supplies?.data.length === 0"
                    title="No Supplies"
                    description="Post a new supply to get started."
                    :icon="Package"
                />

                <template v-else>
                    <div class="grid gap-4 sm:grid-cols-3">
                        <SupplyCard
                            v-for="supply in supplies!.data"
                            :key="supply.id"
                            :supply="supply"
                            @edit="openEdit(supply)"
                            @delete="openDelete(supply)"
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
                        >
                            Previous
                        </Button>
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
                        >
                            Next
                        </Button>
                    </div>
                </template>
            </Deferred>
        </div>
    </AppLayout>

    <SupplyForm
        :open="supplyFormOpen"
        :supply="activeSupply"
        :variety-options="
            varietyOptions as VarietyOptionsByVegetable | undefined
        "
        @update:open="supplyFormOpen = $event"
    />

    <ConfirmationDialog
        v-model:open="deleteDialogOpen"
        title="Delete Supply"
        :description="`Permanently delete this supply for ${supplyToDelete?.scheduled_date}?`"
        :processing="deleteForm.processing"
        variant="destructive"
        @action="handleDelete"
    />
</template>
