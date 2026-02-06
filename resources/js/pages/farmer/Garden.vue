<script setup lang="ts">
import { ref, computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppShell from '@/components/AppShell.vue'
import AppHeader from '@/components/AppHeader.vue'
import AppContent from '@/components/AppContent.vue'
import PlantingSummaryCard from '@/components/features/farmer/cards/PlantingSummaryCard.vue'
import PlantingTable from '@/components/features/farmer/tables/PlantingTable.vue'
import PlantingForm from '@/components/features/farmer/forms/PlantingForm.vue'
import HarvestPlantingDialog from '@/components/features/farmer/dialogs/HarvestPlantingDialog.vue'
import CancelPlantingDialog from '@/components/features/farmer/dialogs/CancelPlantingDialog.vue'
import DeletePlantingDialog from '@/components/features/farmer/dialogs/DeletePlantingDialog.vue'
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import { Plus, Sprout } from 'lucide-vue-next'
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

// Loading states
const isLoadingSummary = computed(() => !props.summary)
const isLoadingPlantings = computed(() => !props.plantings)
const isLoadingOptions = computed(() => !props.varietyOptions)

// Status filter tabs
const statusTabs = [
    { value: 'all', label: 'All' },
    { value: 'active', label: 'Active' },
    { value: 'harvesting_soon', label: 'Harvesting Soon' },
    { value: 'harvested', label: 'Harvested' },
    { value: 'expired', label: 'Expired' },
    { value: 'cancelled', label: 'Cancelled' },
]

const activeTab = computed(() => props.filters.status || 'all')

function handleTabChange(value: string) {
    router.visit(farmer.garden.index().url, {
        data: { status: value === 'all' ? undefined : value },
        preserveScroll: true,
        preserveState: true,
        only: ['plantings'],
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
            status: props.filters.status || undefined 
        },
        preserveScroll: true,
    })
}
</script>

<template>
    <Head title="My Garden" />

    <AppShell variant="header">
        <AppHeader :breadcrumbs="breadcrumbs" />
        
        <AppContent variant="header" class="p-4 lg:p-6">
            <div class="flex flex-col gap-6">
                <!-- Page Header -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                            <Sprout class="size-5" />
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold tracking-tight">My Garden</h1>
                            <p class="mt-1 text-sm text-muted-foreground">
                                Manage your plantings and track harvest schedules
                            </p>
                        </div>
                    </div>

                    <Button @click="openCreate" class="gap-2">
                        <Plus class="size-4" />
                        Add Planting
                    </Button>
                </div>

                <!-- Summary Cards -->
                <PlantingSummaryCard v-if="summary" :summary="summary" />
                <div v-else class="grid grid-cols-1 gap-4 md:grid-cols-4">
                    <Skeleton class="h-24 rounded-lg" />
                    <Skeleton class="h-24 rounded-lg" />
                    <Skeleton class="h-24 rounded-lg" />
                    <Skeleton class="h-24 rounded-lg" />
                </div>

                <!-- Status Filter Tabs -->
                <Tabs :model-value="activeTab" @update:model-value="handleTabChange">
                    <TabsList>
                        <TabsTrigger
                            v-for="tab in statusTabs"
                            :key="tab.value"
                            :value="tab.value"
                        >
                            {{ tab.label }}
                        </TabsTrigger>
                    </TabsList>
                </Tabs>

                <!-- Plantings Table -->
                <PlantingTable
                    v-if="plantings"
                    :plantings="plantings"
                    @open-create="openCreate"
                    @open-edit="openEdit"
                    @open-harvest="openHarvest"
                    @open-cancel="openCancel"
                    @open-delete="openDelete"
                    @page-change="handlePageChange"
                />
                <div v-else class="flex flex-col gap-4">
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
