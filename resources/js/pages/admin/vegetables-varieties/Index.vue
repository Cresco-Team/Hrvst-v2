<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import { useFlash } from '@/composables/useFlash'
import AppLayout from '@/layouts/AppLayout.vue'
import admin from '@/routes/admin'
import type { BreadcrumbItem } from '@/types'
import VarietyTable from '@/components/features/admin/tables/VarietyTable.vue'
import VarietyForm from '@/components/features/admin/forms/VarietyForm.vue'
import VarietyDeleteConfirm from '@/components/features/admin/dialogs/VarietyDeleteConfirm.vue'
import PriceFreshnessFilter from '@/components/features/admin/filters/PriceFreshnessFilter.vue'
import { Skeleton } from '@/components/ui/skeleton'
import Heading from '@/components/Heading.vue'
import LargeCard from '@/components/shared/cards/LargeCard.vue'
import { AlertTriangle, Leaf, TrendingUp } from 'lucide-vue-next'
import { Props, Variety } from '@/types/admin/vegetable-varieties'

const props = withDefaults(defineProps<Props>(), {
    varieties: undefined,
    summary: undefined,
    vegetableOptions: undefined,
})

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: admin.dashboard().url },
    { title: 'Vegetables & Varieties', href: admin.vegetables_varieties.index().url },
]

const { flash } = useFlash()
const toastVisible = ref(false)
let toastTimer: ReturnType<typeof setTimeout> | null = null

function showToast() {
    toastVisible.value = true
    if (toastTimer) clearTimeout(toastTimer)
    toastTimer = setTimeout(() => { toastVisible.value = false }, 4000)
}

router.on('success', () => {
    if (flash.value?.message) showToast()
})

onMounted(() => {
    if (flash.value?.message) showToast()
})

onUnmounted(() => {
    if (toastTimer) clearTimeout(toastTimer)
})

/* -- modal state -- */
const formOpen = ref(false)
const deleteOpen = ref(false)
const activeVariety = ref<Variety | null>(null)
const isSubmitting = ref(false)

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

/* -- filtering -- */
function handleFilterChange(filter: string | null) {
    router.get(
        '/admin/vegetables-varieties',
        { price_filter: filter },
        { preserveScroll: true, preserveState: true }
    )
}

/* -- CRUD via Inertia with FormData -- */
function handleSubmit(formData: FormData) {
    isSubmitting.value = true

    if (activeVariety.value) {
        // UPDATE - Laravel needs _method: PUT for updates with FormData
        formData.append('_method', 'PUT')
        
        router.post(`/admin/vegetables-varieties/${activeVariety.value.id}`, formData, {
            onSuccess() {
                formOpen.value = false
                isSubmitting.value = false
            },
            onError() {
                isSubmitting.value = false
            },
        })
    } else {
        // CREATE
        router.post('/admin/vegetables-varieties', formData, {
            onSuccess() {
                formOpen.value = false
                isSubmitting.value = false
            },
            onError() {
                isSubmitting.value = false
            },
        })
    }
}

function handleDelete() {
    if (!activeVariety.value) return

    router.delete(`/admin/vegetables-varieties/${activeVariety.value.id}`, {
        onSuccess() {
            deleteOpen.value = false
            activeVariety.value = null
        },
    })
}

/* -- server-side pagination -- */
function handlePageChange(page: number) {
    router.get(
        '/admin/vegetables-varieties',
        { page, price_filter: props.filters.price_filter },
        { preserveScroll: true }
    )
}

/* -- loading states -- */
const isLoadingSummary = computed(() => !props.summary)
const isLoadingVarieties = computed(() => !props.varieties)
</script>

<template>
    <Head title="Vegetables & Varieties" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-col gap-6 p-4 lg:p-6">

            <!-- Header -->
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

            <!-- Summary Cards -->
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
            

            <!-- data table -->
            <div v-if="isLoadingVarieties" class="flex flex-col gap-4">
                <div class="flex items-center justify-between">
                    <Skeleton class="h-9 w-64" />
                    <Skeleton class="h-9 w-32" />
                </div>
                <div class="rounded-lg border">
                    <div class="p-4 space-y-3">
                        <Skeleton class="h-12 w-full" />
                        <Skeleton class="h-12 w-full" />
                        <Skeleton class="h-12 w-full" />
                        <Skeleton class="h-12 w-full" />
                        <Skeleton class="h-12 w-full" />
                    </div>
                </div>
            </div>
            <VarietyTable
                v-else-if="varieties"
                :varieties="varieties"
                @open-create="openCreate"
                @open-edit="openEdit"
                @open-delete="openDelete"
                @page-change="handlePageChange"
            />
            
        </div>
    </AppLayout>

    <!-- -- modals -- -->
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

    <!-- -- toast -- -->
    <div
        v-if="toastVisible && flash?.message"
        class="fixed bottom-6 left-1/2 z-100 -translate-x-1/2 flex items-center gap-3 rounded-lg border bg-background px-4 py-3 shadow-lg transition-all"
        :class="flash.type === 'error' ? 'border-destructive' : 'border-primary'"
    >
        <span
            class="inline-flex size-5 items-center justify-center rounded-full text-xs font-bold text-white"
            :class="flash.type === 'error' ? 'bg-destructive' : 'bg-primary'"
        >
            {{ flash.type === 'error' ? '!' : '✓' }}
        </span>
        <span class="text-sm font-medium">{{ flash.message }}</span>
    </div>
</template>
