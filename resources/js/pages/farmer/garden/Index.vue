<script setup lang="ts">
import { ref, computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import PlantingGrid from '@/components/features/farmer/grids/PlantingGrid.vue'
import PlantingForm from '@/components/features/farmer/forms/PlantingForm.vue'
import ArchivePlantingDialog from '@/components/features/farmer/dialogs/ArchivePlantingDialog.vue'
import DeletePlantingDialog from '@/components/features/farmer/dialogs/DeletePlantingDialog.vue'
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import { Plus, Sprout, Weight, Calendar, Package } from 'lucide-vue-next'
import farmer from '@/routes/farmer'
import AppLayout from '@/layouts/AppLayout.vue'
import Heading from '@/components/Heading.vue'
import LargeCard from '@/components/shared/cards/LargeCard.vue'
import type { PaginatedPlantings, Summary, VarietyOptionsByCategory, Planting } from '@/types/farmer/garden'

interface Props {
    filters: {
        status: string | null
        page: number
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
const archiveOpen = ref(false)
const deleteOpen = ref(false)
const activePlanting = ref<Planting | null>(null)
const isSubmitting = ref(false)

// Status filter tabs
const statusTabs = [
    { value: 'available', label: 'Available' },
    { value: 'archived', label: 'Archived' },
]

const activeTab = computed(() => props.filters.status || 'available')

// Loading state detection
const isLoadingPlantings = computed(() => !props.plantings)
const isLoadingSummary = computed(() => !props.summary)
const isLoadingVarieties = computed(() => !props.varietyOptions)

function handleTabChange(value: string) {
    router.visit(farmer.garden.index().url, {
        data: { 
            status: value === 'available' ? undefined : value,
            page: 1,
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

function openArchive(planting: Planting) {
    activePlanting.value = planting
    archiveOpen.value = true
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
            },
            onError() {
                isSubmitting.value = false
            },
        })
    }
}

function handleArchive() {
    if (!activePlanting.value) return

    router.post(farmer.garden.archive(activePlanting.value.id).url, {}, {
        onSuccess() {
            archiveOpen.value = false
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
            page, 
            status: props.filters.status || undefined,
        },
        preserveScroll: true,
        only: ['plantings', 'filters'],
    })
}
</script>

<template>
    <Head title="My Garden" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-col gap-6 p-4 lg:p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <Heading 
                    title="My Garden"
                    description="Track and manage all your plantings in one place"
                />
                <Button @click="openCreate" size="lg" class="gap-2 shadow-lg cursor-pointer">
                    <Plus class="size-5" />
                    Add Planting
                </Button>
            </div>

            <!-- Summary Cards -->
            <div v-if="isLoadingSummary" class="grid gap-4 lg:gap-2 md:grid-cols-2 lg:grid-cols-4">
                <Skeleton class="h-33 rounded-xl" />
                <Skeleton class="h-33 rounded-xl" />
                <Skeleton class="h-33 rounded-xl" />
                <Skeleton class="h-33 rounded-xl" />
            </div>
            <div v-else class="grid gap-4 lg:gap-2 md:grid-cols-2 lg:grid-cols-4">
                <LargeCard 
                    title="Available"
                    subtext="plantings"
                    :value="summary!.total_available"
                    :icon="Sprout"
                    card-class="bg-gradient-to-br from-emerald-50 to-green-50 dark:from-emerald-950/20 dark:to-green-950/20"
                />
                <LargeCard 
                    title="Total Weight"
                    :subtext="`${summary!.total_weight_available} kg`"
                    :value="summary!.total_weight_available"
                    :icon="Weight"
                    card-class="bg-gradient-to-br from-blue-50 to-cyan-50 dark:from-blue-950/20 dark:to-cyan-950/20"
                />
                <LargeCard 
                    title="Expiring Soon"
                    subtext="within 7 days"
                    :value="summary!.expiring_soon"
                    :icon="Calendar"
                    card-class="bg-gradient-to-br from-orange-50 to-amber-50 dark:from-orange-950/20 dark:to-amber-950/20"
                />
                <LargeCard 
                    title="Posted This Month"
                    subtext="new plantings"
                    :value="summary!.posted_this_month"
                    :icon="Package"
                    card-class="bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-950/20 dark:to-pink-950/20"
                />
            </div>

            <!-- Status Filter Tabs -->
            <div class="rounded-xl border-2 bg-card p-1 md:w-1/3">
                <Tabs :model-value="activeTab" @update:model-value="(v) => handleTabChange(String(v))">
                    <TabsList class="w-full grid-cols-3">
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
                v-if="!isLoadingPlantings"
                :plantings="plantings!"
                @open-create="openCreate"
                @open-edit="openEdit"
                @open-archive="openArchive"
                @open-delete="openDelete"
                @page-change="handlePageChange"
            />
            
            <!-- Loading Skeleton -->
            <div v-else class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                <Skeleton v-for="i in 8" :key="i" class="h-96 rounded-xl" />
            </div>
        </div>
    </AppLayout>

    <!-- Modals -->
    <PlantingForm
        v-if="!isLoadingVarieties"
        :open="formOpen"
        :planting="activePlanting"
        :variety-options="varietyOptions!"
        :is-submitting="isSubmitting"
        @update:open="formOpen = $event"
        @submit="handleSubmit"
    />

    <ArchivePlantingDialog
        :open="archiveOpen"
        :planting="activePlanting"
        @update:open="archiveOpen = $event"
        @confirm="handleArchive"
    />

    <DeletePlantingDialog
        :open="deleteOpen"
        :planting="activePlanting"
        @update:open="deleteOpen = $event"
        @confirm="handleDelete"
    />
</template>
