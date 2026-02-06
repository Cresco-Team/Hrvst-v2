<script setup lang="ts">
import { ref, computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppShell from '@/components/AppShell.vue'
import AppHeader from '@/components/AppHeader.vue'
import AppContent from '@/components/AppContent.vue'
import PlantingSummaryCard from '@/components/features/farmer/cards/PlantingSummaryCard.vue'
import PlantingGrid from '@/components/features/farmer/grids/PlantingGrid.vue'
import PlantingForm from '@/components/features/farmer/forms/PlantingForm.vue'
import HarvestPlantingDialog from '@/components/features/farmer/dialogs/HarvestPlantingDialog.vue'
import CancelPlantingDialog from '@/components/features/farmer/dialogs/CancelPlantingDialog.vue'
import DeletePlantingDialog from '@/components/features/farmer/dialogs/DeletePlantingDialog.vue'
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import { Plus, Sprout, Leaf } from 'lucide-vue-next'
import { toast } from 'vue-sonner'
import farmer from '@/routes/farmer'

interface Variety {
    id: number
    name: string
    category: string
    image_path: string
}

interface Planting {
    id: number
    variety: Variety
    weight_kg: number
    date_planted: string
    date_planted_human: string
    expected_harvest_date: string
    days_until_harvest: number | null
    status: string
    status_badge: string
    can_edit: boolean
    can_delete: boolean
    can_harvest: boolean
    can_cancel: boolean
}

interface PaginatedPlantings {
    data: Planting[]
    current_page: number
    last_page: number
    per_page: number
    total: number
}

interface Summary {
    total_active: number
    total_weight_active: number
    harvesting_soon: number
    harvested_this_month: number
}

interface VarietyOptionsByCategory {
    [category: string]: Array<{
        id: number
        name: string
        weeks_to_harvest: number
    }>
}

interface Props {
    filters: {
        status: string | null
        page: number
        search: string | null
    }
    plantings?: PaginatedPlantings
    summary?: Summary
    varietyOptions?: VarietyOptionsByCategory
}

const props = defineProps<Props>()

const breadcrumbs = [
    { title: 'Farmer', href: farmer.garden.index().url },
    { title: 'My Garden', href: farmer.garden.index().url },
]

// Modal state
const formOpen = ref(false)
const harvestOpen = ref(false)
const cancelOpen = ref(false)
const deleteOpen = ref(false)
const activePlanting = ref<Planting | null>(null)
const isSubmitting = ref(false)

// Status filter tabs
const statusTabs = [
    { value: 'all', label: 'All', icon: Sprout },
    { value: 'active', label: 'Growing', icon: Leaf },
    { value: 'harvesting_soon', label: 'Ready Soon', icon: Sprout },
    { value: 'harvested', label: 'Harvested', icon: Sprout },
    { value: 'expired', label: 'Expired', icon: Sprout },
    { value: 'cancelled', label: 'Cancelled', icon: Sprout },
]

const activeTab = computed(() => props.filters.status || 'all')

function handleTabChange(value: string) {
    router.visit(farmer.garden.index().url, {
        data: { 
            status: value === 'all' ? undefined : value,
            page: 1,
            search: props.filters.search || undefined,
        },
        preserveScroll: true,
        preserveState: true,
        only: ['plantings', 'filters'],
    })
}

function handleSearch(query: string) {
    router.visit(farmer.garden.index().url, {
        data: { 
            status: props.filters.status || undefined,
            page: 1,
            search: query || undefined,
        },
        preserveScroll: true,
        preserveState: true,
        only: ['plantings', 'filters'],
    })
}

// CRUD handlers
function openCreate() {
    activePlanting.value = null
    formOpen.value = true
}

function openEdit(planting: Planting) {
    activePlanting.value = planting
    formOpen.value = true
}

function openHarvest(planting: Planting) {
    activePlanting.value = planting
    harvestOpen.value = true
}

function openCancel(planting: Planting) {
    activePlanting.value = planting
    cancelOpen.value = true
}

function openDelete(planting: Planting) {
    activePlanting.value = planting
    deleteOpen.value = true
}

function handleSubmit(formData: FormData) {
    isSubmitting.value = true

    if (activePlanting.value) {
        // UPDATE
        formData.append('_method', 'PUT')
        
        router.post(farmer.garden.update(activePlanting.value.id).url, formData, {
            onSuccess() {
                formOpen.value = false
                isSubmitting.value = false
                toast.success('Planting updated successfully!')
            },
            onError() {
                isSubmitting.value = false
            },
        })
    } else {
        // CREATE
        router.post(farmer.garden.store().url, formData, {
            onSuccess() {
                formOpen.value = false
                isSubmitting.value = false
                toast.success('Planting added successfully!')
            },
            onError() {
                isSubmitting.value = false
            },
        })
    }
}

function handleHarvest(formData: FormData) {
    if (!activePlanting.value) return

    router.post(farmer.garden.harvest(activePlanting.value.id).url, formData, {
        onSuccess() {
            harvestOpen.value = false
            activePlanting.value = null
            toast.success('Planting marked as harvested!')
        },
    })
}

function handleCancel() {
    if (!activePlanting.value) return

    router.post(farmer.garden.cancel(activePlanting.value.id).url, {}, {
        onSuccess() {
            cancelOpen.value = false
            activePlanting.value = null
            toast.success('Planting cancelled.')
        },
    })
}

function handleDelete() {
    if (!activePlanting.value) return

    router.delete(farmer.garden.destroy(activePlanting.value.id).url, {
        onSuccess() {
            deleteOpen.value = false
            activePlanting.value = null
            toast.success('Planting deleted successfully.')
        },
    })
}

function handlePageChange(page: number) {
    router.visit(farmer.garden.index().url, {
        data: { 
            page, 
            status: props.filters.status || undefined,
            search: props.filters.search || undefined,
        },
        preserveScroll: true,
        only: ['plantings', 'filters'],
    })
}
</script>

<template>
    <Head title="My Garden" />

    <AppShell variant="header">
        <AppHeader :breadcrumbs="breadcrumbs" />
        
        <AppContent variant="header" class="p-4 lg:p-8">
            <div class="flex flex-col gap-8">
                <!-- Page Header with Decorative Background -->
                <div class="relative overflow-hidden rounded-2xl border-2 bg-gradient-to-br from-primary/5 via-primary/10 to-primary/5 p-8">
                    <!-- Decorative Elements -->
                    <div class="absolute right-0 top-0 size-64 rounded-full bg-primary/5 blur-3xl" />
                    <div class="absolute bottom-0 left-0 size-48 rounded-full bg-primary/10 blur-3xl" />
                    
                    <div class="relative flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-start gap-4">
                            <div class="flex size-16 shrink-0 items-center justify-center rounded-2xl bg-primary/10 backdrop-blur-sm">
                                <Sprout class="size-8 text-primary" />
                            </div>
                            <div>
                                <h1 class="text-4xl font-bold tracking-tight">My Garden</h1>
                                <p class="mt-2 text-lg text-muted-foreground">
                                    Track and manage all your plantings in one place
                                </p>
                            </div>
                        </div>

                        <Button @click="openCreate" size="lg" class="gap-2 shadow-lg">
                            <Plus class="size-5" />
                            Add Planting
                        </Button>
                    </div>
                </div>

                <!-- Summary Cards -->
                <PlantingSummaryCard v-if="summary" :summary="summary" />
                <div v-else class="grid grid-cols-1 gap-4 md:grid-cols-4">
                    <Skeleton class="h-28 rounded-xl" />
                    <Skeleton class="h-28 rounded-xl" />
                    <Skeleton class="h-28 rounded-xl" />
                    <Skeleton class="h-28 rounded-xl" />
                </div>

                <!-- Status Filter Tabs -->
                <div class="rounded-xl border-2 bg-card p-1">
                    <Tabs :model-value="activeTab" @update:model-value="handleTabChange">
                        <TabsList class="w-full grid-cols-6">
                            <TabsTrigger
                                v-for="tab in statusTabs"
                                :key="tab.value"
                                :value="tab.value"
                                class="data-[state=active]:bg-primary data-[state=active]:text-primary-foreground"
                            >
                                {{ tab.label }}
                            </TabsTrigger>
                        </TabsList>
                    </Tabs>
                </div>

                <!-- Plantings Grid -->
                <PlantingGrid
                    v-if="plantings"
                    :plantings="plantings"
                    :search-query="filters.search || ''"
                    @open-create="openCreate"
                    @open-edit="openEdit"
                    @open-harvest="openHarvest"
                    @open-cancel="openCancel"
                    @open-delete="openDelete"
                    @page-change="handlePageChange"
                    @search="handleSearch"
                />
                <div v-else class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    <Skeleton v-for="i in 8" :key="i" class="h-96 rounded-xl" />
                </div>
            </div>
        </AppContent>
    </AppShell>

    <!-- Modals -->
    <PlantingForm
        v-if="varietyOptions"
        :open="formOpen"
        :planting="activePlanting"
        :variety-options="varietyOptions"
        :is-submitting="isSubmitting"
        @update:open="formOpen = $event"
        @submit="handleSubmit"
    />

    <HarvestPlantingDialog
        :open="harvestOpen"
        :planting="activePlanting"
        @update:open="harvestOpen = $event"
        @confirm="handleHarvest"
    />

    <CancelPlantingDialog
        :open="cancelOpen"
        :planting="activePlanting"
        @update:open="cancelOpen = $event"
        @confirm="handleCancel"
    />

    <DeletePlantingDialog
        :open="deleteOpen"
        :planting="activePlanting"
        @update:open="deleteOpen = $event"
        @confirm="handleDelete"
    />
</template>
