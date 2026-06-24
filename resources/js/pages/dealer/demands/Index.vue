<script setup lang="ts">
import { Deferred, Head, router, useForm } from '@inertiajs/vue3'
import { Plus, ShoppingBag } from 'lucide-vue-next'
import { computed, ref } from 'vue'
import ConfirmationDialog from '@/components/dialogs/ConfirmationDialog.vue'
import EmptyState from '@/components/EmptyState.vue'
import DemandCard from '@/components/features/dealer/DemandCard.vue'
import DemandForm from '@/components/features/dealer/DemandForm.vue'
import Heading from '@/components/Heading.vue'
import LargeCard from '@/components/shared/cards/LargeCard.vue'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs'
import AppLayout from '@/layouts/AppLayout.vue'
import dealer from '@/routes/dealer'
import { destroy, index } from '@/routes/dealer/demands'
import type {
    BreadcrumbItem,
    DealerDemandsProps,
    DealerDemandDataFixed,
    VarietyOptionsByVegetable,
} from '@/types'

const props = defineProps<DealerDemandsProps>()

// ─── Demand form (create + edit) ──────────────────────────────────────────────

const demandFormOpen = ref(false)
const activeDemand = ref<DealerDemandDataFixed | null>(null)

function openCreate() {
    activeDemand.value = null
    demandFormOpen.value = true
}

function openEdit(demand: DealerDemandDataFixed) {
    activeDemand.value = demand
    demandFormOpen.value = true
}

// ─── Delete ───────────────────────────────────────────────────────────────────

const deleteDialogOpen = ref(false)
const demandToDelete = ref<DealerDemandDataFixed | null>(null)
const deleteForm = useForm({})

function openDelete(demand: DealerDemandDataFixed) {
    demandToDelete.value = demand
    deleteDialogOpen.value = true
}

function handleDelete() {
    if (!demandToDelete.value) return
    deleteForm.delete(destroy(demandToDelete.value.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            deleteDialogOpen.value = false
            demandToDelete.value = null
        },
    })
}

// ─── Status tabs + pagination ─────────────────────────────────────────────────

const currentStatus = computed(() => props.filters.status)

function handleStatusChange(value: string | number) {
    router.visit(index({ query: { status: value } }).url, {
        preserveState: true,
        preserveScroll: true,
        only: ['demands', 'filters'],
    })
}

function handlePageChange(page: number) {
    router.visit(index({ query: { status: props.filters.status, page } }).url, {
        preserveScroll: true,
        only: ['demands'],
    })
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dealer', href: dealer.dashboard().url },
    { title: 'Demands', href: dealer.demands.index().url },
]
</script>

<template>
    <Head title="Demands" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-col gap-6 p-4 lg:p-6">
            <div class="flex items-end justify-between">
                <Heading
                    title="My Demands"
                    description="Post purchase requests for farmers."
                />
                <Button class="gap-2" @click="openCreate">
                    <Plus class="size-4" />
                    New Demand
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
                        subtext="awaiting supply"
                    />
                    <LargeCard
                        title="Fulfilled"
                        :value="summary?.total_fulfilled"
                        subtext="completed"
                    />
                    <LargeCard
                        title="Expired"
                        :value="summary?.total_expired"
                        subtext="expired without supply"
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

            <!-- ── Demand list ────────────────────────────────────────── -->
            <Deferred data="demands">
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
                    v-if="demands?.data.length === 0"
                    title="No Demands"
                    description="Post a new demand to get started."
                    :icon="ShoppingBag"
                />

                <template v-else>
                    <div class="grid gap-4 sm:grid-cols-3">
                        <DemandCard
                            v-for="demand in demands!.data"
                            :key="demand.id"
                            :demand="demand"
                            @edit="openEdit(demand)"
                            @delete="openDelete(demand)"
                        />
                    </div>

                    <div
                        v-if="demands && demands.last_page > 1"
                        class="flex items-center justify-between border-t pt-4"
                    >
                        <Button
                            variant="outline"
                            size="sm"
                            :disabled="demands.current_page === 1"
                            @click="handlePageChange(demands.current_page - 1)"
                        >
                            Previous
                        </Button>
                        <span class="text-sm text-muted-foreground">
                            Page {{ demands.current_page }} of
                            {{ demands.last_page }}
                        </span>
                        <Button
                            variant="outline"
                            size="sm"
                            :disabled="
                                demands.current_page === demands.last_page
                            "
                            @click="handlePageChange(demands.current_page + 1)"
                        >
                            Next
                        </Button>
                    </div>
                </template>
            </Deferred>
        </div>
    </AppLayout>

    <DemandForm
        :open="demandFormOpen"
        :demand="activeDemand"
        :variety-options="
            varietyOptions as VarietyOptionsByVegetable | undefined
        "
        @update:open="demandFormOpen = $event"
    />

    <ConfirmationDialog
        v-model:open="deleteDialogOpen"
        title="Delete Demand"
        :description="`Permanently delete this demand for ${demandToDelete?.scheduled_date}?`"
        :processing="deleteForm.processing"
        variant="destructive"
        @action="handleDelete"
    />
</template>
