<script setup lang="ts">
import { Deferred, Head, router } from '@inertiajs/vue3'
import { Plus } from '@lucide/vue'
import { computed, ref } from 'vue'
import ConfirmationDialog from '@/components/dialogs/ConfirmationDialog.vue'
import VegetableForm from '@/components/features/admin/forms/VegetableForm.vue'
import VegetableTable from '@/components/features/admin/tables/VegetableTable.vue'
import Heading from '@/components/Heading.vue'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import AppLayout from '@/layouts/AppLayout.vue'
import type { AdminVegetablesProps, BreadcrumbItem, VegetableAdminData } from '@/types'
import admin, { dashboard } from '@/routes/admin'
import { destroy as destroyVeg, index } from '@/routes/admin/vegetables'

const props = defineProps<AdminVegetablesProps>()

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: 'Admin', href: dashboard().url },
    { title: 'Vegetables', href: admin.categories.index().url },
    ...(props.category
        ? [{ title: props.category.name, href: index({ query: { category: props.category.slug } }).url }]
        : [{ title: 'All Varieties', href: index().url }]),
])

const searchQuery = ref(props.filters?.search ?? '')

const formOpen = ref(false)
const editTarget = ref<VegetableAdminData | null>(null)
const deleteOpen = ref(false)
const deleteTarget = ref<VegetableAdminData | null>(null)

function openCreate(): void {
    editTarget.value = null
    formOpen.value = true
}

function openEdit(row: VegetableAdminData): void {
    editTarget.value = row
    formOpen.value = true
}

function openDelete(row: VegetableAdminData): void {
    deleteTarget.value = row
    deleteOpen.value = true
}

function handleDelete(): void {
    if (!deleteTarget.value) return
    router.delete(destroyVeg(deleteTarget.value.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            deleteOpen.value = false
            deleteTarget.value = null
        },
    })
}

function handlePageChange(page: number) {
    router.visit(admin.vegetables.index().url, {
        data: { page, search: searchQuery.value || undefined },
        preserveState: true,
        preserveScroll: true,
    })
}

function handleSearch(query: string): void {
    searchQuery.value = query
    router.visit(index().url, {
        data: { search: query || undefined, category: props.category?.slug ?? undefined },
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
                    :description="category
                        ? `Vegetables and varieties for ${category.name}`
                        : 'Manage all vegetable and variety entries.'"
                />
                <Button @click="openCreate">
                    <Plus />
                    Add Vegetable
                </Button>
            </div>

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

                <VegetableTable
                    v-if="vegetables"
                    :vegetables="vegetables"
                    :search-query="searchQuery"
                    @open-edit-vegetable="openEdit"
                    @open-delete-vegetable="openDelete"
                    @open-vegetable-details="(row) => router.visit(admin.vegetables.show({ vegetable: row.id }).url)"
                    @page-change="handlePageChange"
                    @search="handleSearch"
                />
            </Deferred>
        </div>
    </AppLayout>

    <VegetableForm
        :open="formOpen"
        :vegetable="editTarget"
        :category-id="category.id"
        :category-name="category.name"
        @update:open="formOpen = $event"
        @success="formOpen = false"
    />

    <ConfirmationDialog
        v-model:open="deleteOpen"
        title="Delete Vegetable"
        :description="`Are you sure you want to delete '${deleteTarget?.display_name}'? This cannot be undone.`"
        variant="destructive"
        @action="handleDelete"
    />
</template>
