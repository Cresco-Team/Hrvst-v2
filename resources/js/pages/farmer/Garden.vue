<script setup lang="ts">
import { ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppShell from '@/components/AppShell.vue'
import AppHeader from '@/components/AppHeader.vue'
import AppContent from '@/components/AppContent.vue'
import PlantingGrid from '@/components/features/farmer/grids/PlantingGrid.vue'
import ArchivedPlantingsTable from '@/components/features/farmer/tables/ArchivedPlantingsTable.vue'
import PlantingForm from '@/components/features/farmer/forms/PlantingForm.vue'
import HarvestPlantingDialog from '@/components/features/farmer/dialogs/HarvestPlantingDialog.vue'
import CancelPlantingDialog from '@/components/features/farmer/dialogs/CancelPlantingDialog.vue'
import DeletePlantingDialog from '@/components/features/farmer/dialogs/DeletePlantingDialog.vue'
import { Tabs, TabsList, TabsTrigger, TabsContent } from '@/components/ui/tabs'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import { Plus, Sprout } from 'lucide-vue-next'
import farmer from '@/routes/farmer'

interface ActivePlanting {
    id: number
    variety: {
        id: number
        name: string
        category: string
        image_path: string
    }
    weight_kg: number
    date_planted: string
    date_planted_human: string
    expected_harvest_date: string
    days_until_harvest: number | null
    status_badge: string
    can_edit: boolean
    can_delete: boolean
    can_harvest: boolean
    can_cancel: boolean
}

interface ArchivedPlanting {
    id: number
    variety_name: string
    category: string
    weight_kg: number
    date_planted: string
    expected_harvest_date: string
    date_completed: string
    status: 'harvested' | 'expired' | 'cancelled'
}

interface PaginatedData<T> {
    data: T[]
    current_page: number
    last_page: number
    per_page: number
    total: number
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
        tab: 'active' | 'archived'
        page: number
        search: string | null
    }
    activePlantings?: PaginatedData<ActivePlanting> | null
    archivedPlantings?: PaginatedData<ArchivedPlanting> | null
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
const activePlanting = ref<ActivePlanting | null>(null)
const isSubmitting = ref(false)

function handleTabChange(value: string | number) {
    const tab = String(value)
    router.visit(farmer.garden.index().url, {
        data: { 
            tab,
            page: 1,
            search: props.filters.search || undefined,
        },
        preserveScroll: false,
        preserveState: true,
        only: ['activePlantings', 'archivedPlantings', 'filters'],
    })
}

function handleSearch(query: string) {
    router.visit(farmer.garden.index().url, {
        data: { 
            tab: props.filters.tab,
            page: 1,
            search: query || undefined,
        },
        preserveScroll: true,
        preserveState: true,
        only: ['activePlantings', 'archivedPlantings', 'filters'],
    })
}

// CRUD handlers
function openCreate() {
    activePlanting.value = null
    formOpen.value = true
}

function openEdit(planting: ActivePlanting) {
    activePlanting.value = planting
    formOpen.value = true
}

function openHarvest(planting: ActivePlanting) {
    activePlanting.value = planting
    harvestOpen.value = true
}

function openCancel(planting: ActivePlanting) {
    activePlanting.value = planting
    cancelOpen.value = true
}

function openDelete(planting: ActivePlanting) {
    activePlanting.value = planting
    deleteOpen.value = true
}

function handleSubmit(formData: FormData) {
    isSubmitting.value = true

    if (activePlanting.value) {
        formData.append('_method', 'PUT')
        
        router.post(farmer.garden.update(activePlanting.value.id).url, formData, {
            onSuccess() {
                formOpen.value = false
                isSubmitting.value = false
            },
            onError() {
                isSubmitting.value = false
            },
        })
    } else {
        router.post(farmer.garden.store().url, formData, {
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

function handleHarvest(formData: FormData) {
    if (!activePlanting.value) return

    router.post(farmer.garden.harvest(activePlanting.value.id).url, formData, {
        onSuccess() {
            harvestOpen.value = false
            activePlanting.value = null
        },
    })
}

function handleCancel() {
    if (!activePlanting.value) return

    router.post(farmer.garden.cancel(activePlanting.value.id).url, {}, {
        onSuccess() {
            cancelOpen.value = false
            activePlanting.value = null
        },
    })
}

function handleDelete() {
    if (!activePlanting.value) return

    router.delete(farmer.garden.destroy(activePlanting.value.id).url, {
        onSuccess() {
            deleteOpen.value = false
            activePlanting.value = null
        },
    })
}

function handlePageChange(page: number) {
    router.visit(farmer.garden.index().url, {
        data: { 
            tab: props.filters.tab,
            page,
            search: props.filters.search || undefined,
        },
        preserveScroll: true,
        only: ['activePlantings', 'archivedPlantings', 'filters'],
    })
}
</script>

<template>
    <Head title="My Garden" />

    <AppShell variant="header">
        <AppHeader :breadcrumbs="breadcrumbs" />
        
        <AppContent variant="header" class="p-4 lg:p-8">
            <div class="flex flex-col gap-6">
                <!-- Page Header -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex size-12 items-center justify-center rounded-xl bg-primary/10">
                            <Sprout class="size-6 text-primary" />
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold">My Garden</h1>
                            <p class="text-sm text-muted-foreground">
                                Manage your plantings
                            </p>
                        </div>
                    </div>

                    <Button @click="openCreate" size="lg" class="gap-2">
                        <Plus class="size-5" />
                        Add Planting
                    </Button>
                </div>

                <!-- Tabs -->
                <Tabs :model-value="filters.tab" @update:model-value="handleTabChange">
                    <TabsList class="grid w-full max-w-md grid-cols-2">
                        <TabsTrigger value="active">Active</TabsTrigger>
                        <TabsTrigger value="archived">Archived</TabsTrigger>
                    </TabsList>

                    <!-- Active Tab Content -->
                    <TabsContent value="active" class="mt-6">
                        <PlantingGrid
                            v-if="activePlantings"
                            :plantings="activePlantings"
                            :search-query="filters.search || ''"
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
                    </TabsContent>

                    <!-- Archived Tab Content -->
                    <TabsContent value="archived" class="mt-6">
                        <ArchivedPlantingsTable
                            v-if="archivedPlantings"
                            :plantings="archivedPlantings"
                            :search-query="filters.search || ''"
                            @page-change="handlePageChange"
                            @search="handleSearch"
                        />
                        <div v-else class="rounded-xl border">
                            <Skeleton class="h-96" />
                        </div>
                    </TabsContent>
                </Tabs>
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
