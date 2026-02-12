<script setup lang="ts">
import PlantingForm from '@/components/features/farmer/forms/PlantingForm.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import farmer from '@/routes/farmer';
import { PaginatedPlantings, Planting, Summary, VarietyOptionsByCategory } from '@/types/farmer/garden';
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface Props {
    filters: {
        status: string | null
        page: number
    }
    plantings?: PaginatedPlantings
    summary: Summary
    varietyOptions?: VarietyOptionsByCategory
}

const breadcrumbs = [
    { title: 'Farmer', href: farmer.garden.index().url },
    { title: 'Farmer', href: farmer.garden.index().url },
]

/* Modal State */
const formOpen = ref(false)
const archiveOpen = ref(false)
const deleteOpen = ref(false)
const cancelOpen = ref(false)
const availablePlanting = ref<Planting | null>(null)

const statusTabs = [
    { value: 'available', label: 'Available' },
    { value: 'archived', label: 'Archived' },
]

const activeTab = computed(() => props.filters.status || 'available')

/* Loading state detection */
const isLoadingPlantings = computed(() => !props.plantings)
const isLoadingSummary = computed(() => !props.summary)
const isLoadingVarieties = computed(() => !props.varietyOptions)

function handleTabChange(value: string) {
    router.visit(farmer.garden.index().url, {
        data: {
            status: value === 'active' ? undefined : value,
            page: 1,
        },
        preserveScroll: true,
        preserveState: true,
        only: ['plantings', 'filters'],
    })
}

const props = defineProps<Props>()

/* CRUD Handlers */
function openCreate() {
    availablePlanting.value = null
    formOpen.value = true
}

function openArchive(planting: Planting) {
    availablePlanting.value = planting
}

function openEdit(planting: Planting) {
    availablePlanting.value = planting
    formOpen.value = true
}

function openDelete(planting: Planting) {
    availablePlanting.value = planting
    cancelOpen.value = true
}
</script>

<template>
    <Head title="My Garden"/>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-col gap-6 p-4 lg:p-6">
            
            <!-- Header -->
             <div class="flex items-center justify-between">
                <Heading title="My Garden" description="Track and manage all your plantings in one place" />
                <Button @click="openCreate">

                </Button>
             </div>
        </div>
    </AppLayout>

    <!-- Modals -->
    <PlantingForm 
        v-if="!isLoadingVarieties"
        :open="formOpen"
        :planting="availablePlanting"
        :variety-options="varietyOptions!"
        :is-submitting="isSubmitting"
        @update:open="formOpen = $event"
        @submit="handleSubmit"
    />
</template>