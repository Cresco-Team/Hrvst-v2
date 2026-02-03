<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { Head } from '@inertiajs/vue3'
import { router, usePage } from '@inertiajs/vue3'
import { useFlash } from '@/composables/useFlash'
import AppLayout from '@/layouts/AppLayout.vue'
import admin from '@/routes/admin'
import type { BreadcrumbItem } from '@/types'

/* -- feature components -- */
import VegetableSummaryCards from '@/components/features/admin/cards/VegetableSummaryCard.vue'
import VegetableTable from '@/components/features/admin/tables/VegetableTable.vue'
import VegetableForm from '@/components/features/admin/forms/VegetableForm.vue'
import VegetableDeleteConfirm from '@/components/features/admin/dialogs/VegetableDeleteConfirm.vue'

/* -- types -- */
interface Vegetable {
    id: number
    category_id: number
    name: string
    varieties_count: number
    category: { id: number, name: string }
}

interface Summary {
    total_vegetables: number
    total_categories: number
    total_varieties: number
    categories: { id: number, name: string, vegetables_count: number }[]
}

interface Props {
    vegetables: {
        data: Vegetable[]
        current_page: number
        last_page: number
        per_page: number
        total: number
    }
    summary: Summary
    categoryOptions: Record<number, string>
}

const props = defineProps<Props>()

/* -- breadcrumbs -- */
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: admin.dashboard().url },
    { title: 'Vegetables', href: admin.vegetables.index().url },
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

// watch flash changes on every navigation
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
const activeVegetable = ref<Vegetable | null>(null)
const isSubmitting = ref(false)

function openCreate() {
    activeVegetable.value = null
    formOpen.value = true
}

function openEdit(vegetable: Vegetable) {
    activeVegetable.value = vegetable
    formOpen.value = true
}

function openDelete(vegetable: Vegetable) {
    activeVegetable.value = vegetable
    deleteOpen.value = true
}

/* -- CRUD via Inertia -- */
function handleSubmit(payload: { category_id: number, name: string }) {
    isSubmitting.value = true

    if (activeVegetable.value) {
        // UPDATE
        router.put(`/admin/vegetables/${activeVegetable.value.id}`, payload, {
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
        router.post('/admin/vegetables', payload, {
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
    if (!activeVegetable.value) return

    router.delete(`/admin/vegetables/${activeVegetable.value.id}`, {
        onSuccess() {
            deleteOpen.value = false
            activeVegetable.value = null
        },
    })
}

/* -- server-side pagination -- */
function handlePageChange(page: number) {
    router.get('/admin/vegetables', { page }, { preserveScroll: true })
}
</script>

<template>
    <Head title="Vegetables" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4 lg:p-6">

            <!-- page header -->
            <div class="flex items-end justify-between">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">Vegetables</h1>
                    <p class="text-sm text-muted-foreground mt-0.5">
                        Manage all registered vegetables and their categories.
                    </p>
                </div>
            </div>

            <!-- summary cards -->
            <VegetableSummaryCards :summary="summary" />

            <!-- data table -->
            <VegetableTable
                :vegetables="vegetables"
                @open-create="openCreate"
                @open-edit="openEdit"
                @open-delete="openDelete"
                @page-change="handlePageChange"
            />
        </div>
    </AppLayout>

    <!-- -- modals -- -->
    <VegetableForm
        :open="formOpen"
        :vegetable="activeVegetable"
        :category-options="categoryOptions"
        :is-submitting="isSubmitting"
        @update:open="formOpen = $event"
        @submit="handleSubmit"
    />

    <VegetableDeleteConfirm
        :open="deleteOpen"
        :vegetable="activeVegetable"
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