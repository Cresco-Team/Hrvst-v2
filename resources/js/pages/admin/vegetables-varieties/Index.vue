<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import { useFlash } from '@/composables/useFlash'
import AppLayout from '@/layouts/AppLayout.vue'
import admin from '@/routes/admin'
import type { BreadcrumbItem } from '@/types'

/* -- feature components -- */
import VarietySummaryCards from '@/components/features/admin/cards/VarietySummaryCard.vue'
import VarietyTable from '@/components/features/admin/tables/VarietyTable.vue'
import VarietyForm from '@/components/features/admin/forms/VarietyForm.vue'
import VarietyDeleteConfirm from '@/components/features/admin/dialogs/VarietyDeleteConfirm.vue'
import PriceFreshnessFilter from '@/components/features/admin/filters/PriceFreshnessFilter.vue'

/* -- types -- */
interface Variety {
    id: number
    vegetable_id: number
    name: string
    image_path: string
    image_url?: string
    weeks_to_harvest: number
    vegetable: {
        id: number
        name: string
        category: {
            id: number
            name: string
        }
    }
    latest_price: {
        price_min: string
        price_max: string
    } | null
    price_updated_human?: string
    price_updated_date?: string
    price_freshness?: 'fresh' | 'recent' | 'okay' | 'aging' | 'stale'
}

interface Summary {
    total_varieties: number
    total_vegetables: number
    average_weeks_to_harvest: number
    price_stats: {
        updated_week: number
        updated_month: number
        stale: number
        no_price: number
    }
}

interface VegetableOptions {
    [categoryName: string]: {
        [vegetableId: number]: string
    }
}

interface Props {
    varieties: {
        data: Variety[]
        current_page: number
        last_page: number
        per_page: number
        total: number
    }
    summary: Summary
    vegetableOptions: VegetableOptions
    filters: {
        price_filter: string | null
    }
}

const props = defineProps<Props>()

/* -- breadcrumbs -- */
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: admin.dashboard().url },
    { title: 'Vegetables & Varieties', href: admin.vegetables_varieties.index().url },
]

/* -- flash / toast -- */
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
</script>

<template>
    <Head title="Vegetables & Varieties" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4 lg:p-6">

            <!-- page header with filter -->
            <div class="flex items-end justify-between">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">Vegetables & Varieties</h1>
                    <p class="text-sm text-muted-foreground mt-0.5">
                        Manage all vegetable varieties, prices, and harvest times.
                    </p>
                </div>
                
                <!-- Price Freshness Filter -->
                <PriceFreshnessFilter
                    :active-filter="filters.price_filter"
                    :price-stats="summary.price_stats"
                    @filter-change="handleFilterChange"
                />
            </div>

            <!-- summary cards -->
            <VarietySummaryCards :summary="summary" />

            <!-- data table -->
            <VarietyTable
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
