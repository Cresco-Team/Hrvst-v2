<script setup lang="ts">
import { Deferred, Head, router, useForm } from '@inertiajs/vue3'
import { ShoppingBag } from 'lucide-vue-next'
import { computed, ref } from 'vue'
import ConfirmationDialog from '@/components/dialogs/ConfirmationDialog.vue'
import EmptyState from '@/components/EmptyState.vue'
import DemandCard from '@/components/features/dealer/DemandCard.vue'
import Heading from '@/components/Heading.vue'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs'
import AppLayout from '@/layouts/AppLayout.vue'
import dealer from '@/routes/dealer'
import { archived, index } from '@/routes/dealer/demands'
import type { BreadcrumbItem, DealerDemandDataFixed, PaginatedData } from '@/types'

// Add DealerArchivedProps to @/types:
// interface DealerArchivedProps {
//     filters: { status: string }
//     demands?: PaginatedData<DealerDemandDataFixed>
// }
interface Props {
    filters: { status: string }
    demands?: PaginatedData<DealerDemandDataFixed>
}

const props = defineProps<Props>()

// ─── Delete ───────────────────────────────────────────────────────────────────
// No create/edit on archived items — editing history is a data integrity risk.
// DemandCard still emits @edit; wire it up if your policy changes. For now,
// only delete is wired so cards should conditionally hide their edit button
// (add a :readonly prop to DemandCard to suppress the edit action).

const deleteDialogOpen = ref(false)
const demandToDelete = ref<DealerDemandDataFixed | null>(null)
const deleteForm = useForm({})

function openDelete(demand: DealerDemandDataFixed) {
    demandToDelete.value = demand
    deleteDialogOpen.value = true
}

function handleDelete() {
    if (!demandToDelete.value) return
    deleteForm.delete(`/dealer/demands/${demandToDelete.value.id}`, {
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
    router.visit(archived({ query: { status: value } }).url, {
        preserveState: true,
        preserveScroll: true,
        only: ['demands', 'filters'],
    })
}

function handlePageChange(page: number) {
    router.visit(archived({ query: { status: props.filters.status, page } }).url, {
        preserveScroll: true,
        only: ['demands'],
    })
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dealer', href: dealer.dashboard().url },
    { title: 'Requests', href: index().url },
    { title: 'Archived', href: archived().url },
]
</script>

<template>
    <Head title="Archived Requests" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-col gap-6 p-4 lg:p-6">
            <Heading
                title="Archived Requests"
                description="Expired and fulfilled demand schedules."
            />

            <!-- ── Status tabs ────────────────────────────────────────── -->
            <Tabs
                :model-value="currentStatus"
                @update:model-value="handleStatusChange"
            >
                <TabsList>
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
                    :title="
                        currentStatus === 'expired'
                            ? 'No Expired Requests'
                            : 'No Fulfilled Requests'
                    "
                    :description="
                        currentStatus === 'expired'
                            ? 'Requests that pass their scheduled date without supply appear here.'
                            : 'Completed requests appear here.'
                    "
                    :icon="ShoppingBag"
                />

                <template v-else>
                    <div class="grid gap-4 sm:grid-cols-3">
                        <DemandCard
                            v-for="demand in demands!.data"
                            :key="demand.id"
                            :demand="demand"
                            :readonly="true"
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

    <ConfirmationDialog
        v-model:open="deleteDialogOpen"
        title="Delete Request"
        :description="`Permanently delete this request for ${demandToDelete?.scheduled_date}?`"
        :processing="deleteForm.processing"
        variant="destructive"
        @action="handleDelete"
    />
</template>
