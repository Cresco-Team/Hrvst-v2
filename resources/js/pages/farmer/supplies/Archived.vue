<script setup lang="ts">
import { Deferred, Head, router, useForm } from '@inertiajs/vue3'
import { Package } from 'lucide-vue-next'
import { computed, ref } from 'vue'
import ConfirmationDialog from '@/components/dialogs/ConfirmationDialog.vue'
import EmptyState from '@/components/EmptyState.vue'
import SupplyCard from '@/components/features/farmer/SupplyCard.vue'
import Heading from '@/components/Heading.vue'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs'
import AppLayout from '@/layouts/AppLayout.vue'
import farmer from '@/routes/farmer'
import { archived, index } from '@/routes/farmer/supplies'
import type { BreadcrumbItem, FarmerSupplyDataFixed, PaginatedData } from '@/types'

// Add FarmerArchivedProps to @/types:
// interface FarmerArchivedProps {
//     filters: { status: string }
//     supplies?: PaginatedData<FarmerSupplyDataFixed>
// }
interface Props {
    filters: { status: string }
    supplies?: PaginatedData<FarmerSupplyDataFixed>
}

const props = defineProps<Props>()

// ─── Delete ───────────────────────────────────────────────────────────────────
// No create/edit on archived items — editing history is a data integrity risk.
// SupplyCard still emits @edit; wire it up if your policy changes. For now,
// only delete is wired so cards should conditionally hide their edit button
// (add a :readonly prop to SupplyCard to suppress the edit action).

const deleteDialogOpen = ref(false)
const supplyToDelete = ref<FarmerSupplyDataFixed | null>(null)
const deleteForm = useForm({})

function openDelete(supply: FarmerSupplyDataFixed) {
    supplyToDelete.value = supply
    deleteDialogOpen.value = true
}

function handleDelete() {
    if (!supplyToDelete.value) return
    deleteForm.delete(`/farmer/supplies/${supplyToDelete.value.id}`, {
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
    router.visit(archived({ query: { status: value } }).url, {
        preserveState: true,
        preserveScroll: true,
        only: ['supplies', 'filters'],
    })
}

function handlePageChange(page: number) {
    router.visit(archived({ query: { status: props.filters.status, page } }).url, {
        preserveScroll: true,
        only: ['supplies'],
    })
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Farmer', href: farmer.dashboard().url },
    { title: 'Supplies', href: index().url },
    { title: 'Archived', href: archived().url },
]
</script>

<template>
    <Head title="Archived Supplies" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-col gap-6 p-4 lg:p-6">
            <Heading
                title="Archived Supplies"
                description="Expired and fulfilled supply schedules."
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
                    :title="
                        currentStatus === 'expired'
                            ? 'No Expired Supplies'
                            : 'No Fulfilled Supplies'
                    "
                    :description="
                        currentStatus === 'expired'
                            ? 'Supplies that pass their scheduled date without fulfillment appear here.'
                            : 'Completed supplies appear here.'
                    "
                    :icon="Package"
                />

                <template v-else>
                    <div class="grid gap-4 sm:grid-cols-3">
                        <SupplyCard
                            v-for="supply in supplies!.data"
                            :key="supply.id"
                            :supply="supply"
                            :readonly="true"
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

    <ConfirmationDialog
        v-model:open="deleteDialogOpen"
        title="Delete Supply"
        :description="`Permanently delete this supply for ${supplyToDelete?.scheduled_date}?`"
        :processing="deleteForm.processing"
        variant="destructive"
        @action="handleDelete"
    />
</template>
