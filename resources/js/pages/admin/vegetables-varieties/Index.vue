<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import axios from 'axios'
import { AlertTriangle, Leaf, TrendingUp } from 'lucide-vue-next'
import { ref, computed } from 'vue'
import { toast } from 'vue-sonner'
import VarietyDeleteConfirm from '@/components/features/admin/dialogs/VarietyDeleteConfirm.vue'
import PriceFreshnessFilter from '@/components/features/admin/filters/PriceFreshnessFilter.vue'
import VarietyForm from '@/components/features/admin/forms/VarietyForm.vue'
import VarietyTable from '@/components/features/admin/tables/VarietyTable.vue'
import Heading from '@/components/Heading.vue'
import LargeCard from '@/components/shared/cards/LargeCard.vue'
import VegetableDetailDialog from '@/components/shared/VegetableDetailDialog.vue'
import { Skeleton } from '@/components/ui/skeleton'
import AppLayout from '@/layouts/AppLayout.vue'
import admin, { dashboard } from '@/routes/admin'
import { index, details as varietyDetails } from '@/routes/admin/vegetables_varieties'
import type { BreadcrumbItem } from '@/types'
import type { Props, Variety } from '@/types/admin/vegetable-varieties'
import type { CatalogVariety } from '@/types/shared/vegetables'

const props = withDefaults(defineProps<Props>(), {
    varieties: undefined,
    summary: undefined,
    vegetableOptions: undefined,
})

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: dashboard().url },
    { title: 'Vegetables & Varieties', href: index().url },
]

/* -- CRUD modal state -- */
const formOpen = ref(false)
const deleteOpen = ref(false)
const activeVariety = ref<Variety | null>(null)
const isSubmitting = ref(false)

/* -- detail dialog state -- */
const detailOpen = ref(false)
const detailVariety = ref<CatalogVariety | null>(null)
const loadingDetail = ref(false)

function openCreate() {
    activeVariety.value = null
    formOpen.value = true
}

function openEdit(variety: Variety) {
    activeVariety.value = variety
    formOpen.value = true
}

function openDelete(variety: Variety) {
    activeVariety.value = variety
    deleteOpen.value = true
}

async function openView(variety: Variety) {
    loadingDetail.value = true
    detailVariety.value = null
    detailOpen.value = true

    try {
        const { data } = await axios.get(varietyDetails(variety.id).url)
        detailVariety.value = data
    } catch (error) {
        console.error('openView failed:', error)
        toast.error('Failed to load variety details')
        detailOpen.value = false
    } finally {
        loadingDetail.value = false
    }
}

/* -- filtering -- */
function handleFilterChange(filter: string | null) {
    router.get(
        index().url,
        { price_filter: filter },
        { preserveScroll: true, preserveState: true }
    )
}

/* -- CRUD via Inertia with FormData -- */
function handleSubmit(formData: FormData) {
    isSubmitting.value = true

    if (activeVariety.value) {
        formData.append('_method', 'PUT')
        router.post(`/admin/vegetables-varieties/${activeVariety.value.id}`, formData, {
            onSuccess() { formOpen.value = false; isSubmitting.value = false },
            onError() { isSubmitting.value = false },
        })
    } else {
        router.post('/admin/vegetables-varieties', formData, {
            onSuccess() { formOpen.value = false; isSubmitting.value = false },
            onError() { isSubmitting.value = false },
        })
    }
}

function handleDelete() {
    if (!activeVariety.value) return
    router.delete(`/admin/vegetables-varieties/${activeVariety.value.id}`, {
        onSuccess() { deleteOpen.value = false; activeVariety.value = null },
    })
}

function handlePageChange(page: number) {
    router.get(
        admin.vegetables_varieties.index().url,
        { page, price_filter: props.filters.price_filter },
        { preserveScroll: true }
    )
}

const isLoadingSummary = computed(() => !props.summary)
const isLoadingVarieties = computed(() => !props.varieties)
</script>

<template>
    <Head title="Vegetables & Varieties" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-col gap-6 p-4 lg:p-6">

            <div class="flex items-end justify-between">
                <Heading
                    title="Vegetables"
                    description="Manage all vegetable varieties, prices, and harvest times."
                />
                <PriceFreshnessFilter
                    v-if="summary"
                    :active-filter="filters.price_filter"
                    :price-stats="summary.price_stats"
                    @filter-change="handleFilterChange"
                />
                <Skeleton v-else class="h-9 w-32" />
            </div>

            <div v-if="isLoadingSummary" class="grid md:grid-cols-3 gap-4">
                <Skeleton class="h-33" />
                <Skeleton class="h-33" />
                <Skeleton class="h-33" />
            </div>
            <div v-else-if="summary" class="grid md:grid-cols-3 gap-4">
                <LargeCard
                    title="Total Varieties"
                    subtext="available for planting"
                    :value="summary.total_varieties"
                    :icon="Leaf"
                    cardClass="md:col-span-1 bg-linear-to-br from-lime-500/10 to-green-500/30"
                />
                <LargeCard
                    title="Price Updates"
                    subtext="this week"
                    :value="summary.price_stats.updated_week"
                    :icon="TrendingUp"
                    cardClass="md:col-span-1 bg-linear-to-br from-lime-500/10 to-green-500/30"
                />
                <LargeCard
                    title="Needs Attention"
                    subtext="varieties"
                    :value="summary.price_stats.stale"
                    :icon="AlertTriangle"
                    iconColor="text-orange-500"
                    cardClass="md:col-span-1 bg-linear-to-br from-red-500/20 via-green-500/10 to-green-500/30"
                />
            </div>

            <div v-if="isLoadingVarieties" class="flex flex-col gap-4">
                <div class="flex items-center justify-between">
                    <Skeleton class="h-9 w-64" />
                    <Skeleton class="h-9 w-32" />
                </div>
                <div class="rounded-lg border">
                    <div class="p-4 space-y-3">
                        <Skeleton v-for="n in 5" :key="n" class="h-12 w-full" />
                    </div>
                </div>
            </div>
            <VarietyTable
                v-else-if="varieties"
                :varieties="varieties"
                @open-create="openCreate"
                @open-view="openView"
                @open-edit="openEdit"
                @open-delete="openDelete"
                @page-change="handlePageChange"
            />

        </div>
    </AppLayout>

    <VarietyForm
        v-if="vegetableOptions"
        :open="formOpen"
        :variety="activeVariety"
        :vegetable-options="vegetableOptions"
        :is-submitting="isSubmitting"
        @update:open="formOpen = $event"
        @submit="handleSubmit"
    />

    <VarietyDeleteConfirm
        :open="deleteOpen"
        :variety="activeVariety"
        @update:open="deleteOpen = $event"
        @confirm="handleDelete"
    />

    <VegetableDetailDialog
        :open="detailOpen"
        :variety="detailVariety"
        @update:open="detailOpen = $event"
    />
</template>
