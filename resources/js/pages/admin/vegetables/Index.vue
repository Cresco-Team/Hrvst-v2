<script setup lang="ts">
import { Deferred, Head, router } from '@inertiajs/vue3'
import { AlertTriangle } from 'lucide-vue-next'
import { computed, ref } from 'vue'
import ConfirmationDialog from '@/components/dialogs/ConfirmationDialog.vue'
import PriceFreshnessFilter from '@/components/features/admin/filters/PriceFreshnessFilter.vue'
import PriceUpdateForm from '@/components/features/admin/forms/PriceUpdateForm.vue'
import VarietyForm from '@/components/features/admin/forms/VarietyForm.vue'
import CreateVegetable from '@/components/features/admin/forms/CreateVegetable.vue'
import UpdateVegetable from '@/components/features/admin/forms/UpdateVegetable.vue'
import VarietyTable from '@/components/features/admin/tables/VarietyTable.vue'
import Heading from '@/components/Heading.vue'
import LargeCard from '@/components/shared/cards/LargeCard.vue'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import AppLayout from '@/layouts/AppLayout.vue'
import admin, { dashboard } from '@/routes/admin'
import { destroy as destroyVeg, index } from '@/routes/admin/vegetables'
import type {
    AdminVegetablesProps,
    BreadcrumbItem,
    VarietyResource,
} from '@/types'
import {
    mapVegetablesToTableRows,
    type VarietyTableRow,
} from '@/types/resources/product'

const props = defineProps<AdminVegetablesProps>()

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: 'Admin', href: dashboard().url },
    { title: 'Vegetables', href: admin.categories.index().url },
    ...(props.category
        ? [
              {
                  title: props.category.name,
                  href: index({ query: { category: props.category.slug } }).url,
              },
          ]
        : [{ title: 'All Varieties', href: index().url }]),
])

const searchQuery = ref(props.filters?.search ?? '')
const tableVegetables = computed(() =>
    mapVegetablesToTableRows(props.vegetables.data ?? []),
)

// ── Variety CRUD ───────────────────────────────────────────────────────────────

const varietyFormOpen = ref(false)
const varietyDeleteOpen = ref(false)
const activeVariety = ref<VarietyResource | null>(null)
const activeParentVegetable = ref<{ id: number; name: string } | null>(null)
const varietyDeleteTarget = ref<VarietyTableRow | null>(null)

function openCreateVariety(parentRow: VarietyTableRow): void {
    activeVariety.value = null
    activeParentVegetable.value = { id: parentRow.id, name: parentRow.name }
    varietyFormOpen.value = true
}

function openEditVariety(row: VarietyTableRow): void {
    const parentVeg = tableVegetables.value.find(
        (v) => v.id === row.vegetable_id,
    )
    activeParentVegetable.value = parentVeg
        ? { id: parentVeg.id, name: parentVeg.name }
        : null
    activeVariety.value = {
        id: row.id,
        name: row.name,
        image_url: row.image_url ?? '',
        hearts_count: 0,
        is_hearted: false,
        vegetable: {
            id: row.vegetable_id ?? 0,
            name: activeParentVegetable.value?.name ?? '',
            category: null,
        },
        latest_price: row.latest_price ?? null,
    } as unknown as VarietyResource
    varietyFormOpen.value = true
}

function openDeleteVariety(row: VarietyTableRow): void {
    varietyDeleteTarget.value = row
    varietyDeleteOpen.value = true
}

function handleDeleteVariety(): void {
    if (!varietyDeleteTarget.value) return
    router.delete(
        admin.vegetables.varieties.destroy({
            variety: varietyDeleteTarget.value.id,
        }).url,
        {
            preserveScroll: true,
            onSuccess: () => {
                varietyDeleteOpen.value = false
                varietyDeleteTarget.value = null
            },
        },
    )
}

// ── Vegetable CRUD ─────────────────────────────────────────────────────────────

const vegCreateOpen = ref(false)
const vegUpdateOpen = ref(false)
const vegDeleteOpen = ref(false)
const vegEditTarget = ref<VarietyTableRow | null>(null)
const vegDeleteTarget = ref<VarietyTableRow | null>(null)

function openCreateVegetable(): void {
    vegCreateOpen.value = true
}

function openEditVegetable(row: VarietyTableRow): void {
    vegEditTarget.value = row
    vegUpdateOpen.value = true
}

function openDeleteVegetable(row: VarietyTableRow): void {
    vegDeleteTarget.value = row
    vegDeleteOpen.value = true
}

function handleDeleteVegetable(): void {
    if (!vegDeleteTarget.value) return
    router.delete(destroyVeg(vegDeleteTarget.value.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            vegDeleteOpen.value = false
            vegDeleteTarget.value = null
        },
    })
}

// ── Price update ───────────────────────────────────────────────────────────────

const priceFormOpen = ref(false)
const priceVariety = ref<VarietyResource | null>(null)

function openUpdatePrice(row: VarietyTableRow): void {
    priceVariety.value = {
        id: row.id,
        name: row.name,
        image_url: row.image_url ?? '',
        hearts_count: 0,
        is_hearted: false,
        vegetable: { id: 0, name: '', category: null },
        latest_price: row.latest_price ?? null,
    } as unknown as VarietyResource
    priceFormOpen.value = true
}

// ── Filtering ─────────────────────────────────────────────────────────────────

function handleFilterChange(filter: string | null): void {
    router.get(
        index().url,
        { price_filter: filter, category: props.category?.slug ?? undefined },
        { preserveScroll: true, preserveState: true },
    )
}

function handleSearch(query: string): void {
    searchQuery.value = query
    router.visit(index().url, {
        data: {
            search: query || undefined,
            price_filter: props.filters.price_filter || undefined,
            category: props.category?.slug ?? undefined,
        },
        preserveState: true,
        preserveScroll: true,
        only: ['vegetables', 'filters'],
    })
}
</script>

<template>
    <Head title="Vegetables" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 lg:p-6">
            <div class="flex items-end justify-between">
                <Heading
                    title="Vegetables"
                    :description="
                        category
                            ? `Lists of Varieties for ${category.name}`
                            : 'Manage all vegetable types and their varieties.'
                    "
                />
                <div class="flex items-center gap-2">
                    <PriceFreshnessFilter
                        v-if="summary"
                        :active-filter="filters.price_filter"
                        :price-stats="summary.price_stats"
                        @filter-change="handleFilterChange"
                    />
                    <Skeleton v-else class="h-9 w-32" />
                    <Button @click="openCreateVegetable">Add Vegetable</Button>
                </div>
            </div>

            <!-- Summary cards -->
            <Deferred data="summary">
                <template #fallback>
                    <div class="grid gap-4 md:grid-cols-3">
                        <Skeleton v-for="i in 3" :key="i" class="h-32" />
                    </div>
                </template>
                <div class="grid gap-4 md:grid-cols-3">
                    <LargeCard
                        title="Vegetable Varieties"
                        subtext="total"
                        :value="summary?.total_varieties"
                    />
                    <LargeCard
                        title="Price Updates"
                        subtext="updated this week"
                        :value="summary?.price_stats.updated_week"
                    />
                    <LargeCard
                        title="Needs Attention"
                        subtext="varieties"
                        :value="summary?.price_stats.stale"
                        :icon="AlertTriangle"
                        icon-color="text-orange-500"
                    />
                </div>
            </Deferred>

            <!-- Hierarchical table -->
            <Deferred data="vegetables">
                <template #fallback>
                    <div class="flex flex-col gap-3">
                        <Skeleton class="h-9 w-72" />
                        <div class="rounded-lg border">
                            <div class="space-y-2 p-4">
                                <Skeleton
                                    v-for="i in 6"
                                    :key="i"
                                    class="h-11 w-full"
                                />
                            </div>
                        </div>
                    </div>
                </template>

                <VarietyTable
                    v-if="vegetables"
                    :vegetables="tableVegetables"
                    :search-query="searchQuery"
                    @open-edit-vegetable="openEditVegetable"
                    @open-delete-vegetable="openDeleteVegetable"
                    @open-create-variety="openCreateVariety"
                    @open-edit-variety="openEditVariety"
                    @open-delete-variety="openDeleteVariety"
                    @open-update-price="openUpdatePrice"
                    @open-variety-details="
                        (row) =>
                            router.visit(
                                admin.vegetables.varieties.show({
                                    variety: row.id,
                                }).url,
                            )
                    "
                    @search="handleSearch"
                />
            </Deferred>
        </div>
    </AppLayout>

    <!-- Variety forms -->
    <VarietyForm
        :open="varietyFormOpen"
        :variety="activeVariety"
        :parent-vegetable="activeParentVegetable"
        @update:open="varietyFormOpen = $event"
        @success="varietyFormOpen = false"
    />

    <ConfirmationDialog
        v-model:open="varietyDeleteOpen"
        title="Delete Variety"
        :description="`Are you sure you want to delete '${varietyDeleteTarget?.name}'?`"
        variant="destructive"
        @action="handleDeleteVariety"
    />

    <!-- Vegetable forms -->
    <CreateVegetable
        :open="vegCreateOpen"
        :category-id="category?.id"
        @update:open="vegCreateOpen = $event"
        @success="vegCreateOpen = false"
    />

    <UpdateVegetable
        :open="vegUpdateOpen"
        :vegetable="vegEditTarget"
        @update:open="vegUpdateOpen = $event"
        @success="vegUpdateOpen = false"
    />

    <ConfirmationDialog
        v-model:open="vegDeleteOpen"
        title="Delete Vegetable"
        :description="
            (vegDeleteTarget?.varieties?.length ?? 0) > 0
                ? `'${vegDeleteTarget?.name}' still has varieties. Remove them first.`
                : `Are you sure you want to delete '${vegDeleteTarget?.name}'?`
        "
        :action-name="
            (vegDeleteTarget?.varieties?.length ?? 0) > 0 ? 'OK' : 'Delete'
        "
        :variant="
            (vegDeleteTarget?.varieties?.length ?? 0) > 0
                ? 'default'
                : 'destructive'
        "
        @action="
            (vegDeleteTarget?.varieties?.length ?? 0) > 0
                ? (vegDeleteOpen = false)
                : handleDeleteVegetable()
        "
    />

    <!-- Price update -->
    <PriceUpdateForm
        v-if="priceVariety"
        :open="priceFormOpen"
        :variety="priceVariety"
        :is-submitting="false"
        @update:open="priceFormOpen = $event"
    />
</template>
