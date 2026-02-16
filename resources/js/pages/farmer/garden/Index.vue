<script setup lang="ts">
import { ref, computed } from 'vue'
import { Deferred, Head, router, useForm } from '@inertiajs/vue3'
import PlantingGrid from '@/components/features/farmer/grids/PlantingGrid.vue'
import PlantingForm from '@/components/features/farmer/forms/PlantingForm.vue'
import ArchivePlantingDialog from '@/components/features/farmer/dialogs/ArchivePlantingDialog.vue'
import DeletePlantingDialog from '@/components/features/farmer/dialogs/DeletePlantingDialog.vue'
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import { Plus, Sprout, Weight, Calendar, Package, Wheat, Archive, CalendarClock, Search, PhilippinePeso, Pencil, MoreVertical, Trash } from 'lucide-vue-next'
import farmer from '@/routes/farmer'
import AppLayout from '@/layouts/AppLayout.vue'
import Heading from '@/components/Heading.vue'
import LargeCard from '@/components/shared/cards/LargeCard.vue'
import type { PaginatedPlantings, Summary, VarietyOptionsByCategory, Planting } from '@/types/farmer/garden'
import { InputGroup, InputGroupAddon } from '@/components/ui/input-group'
import { Empty, EmptyContent, EmptyDescription, EmptyHeader, EmptyMedia } from '@/components/ui/empty'
import EmptyState from '@/components/EmptyState.vue'
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card'
import { Item, ItemContent, ItemDescription, ItemMedia, ItemTitle } from '@/components/ui/item'
import { Badge } from '@/components/ui/badge'
import { AspectRatio } from '@/components/ui/aspect-ratio'
import { Separator } from '@/components/ui/separator'
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu'
import GardenCard from '@/components/farmer/cards/GardenCard.vue'
import DialogForm from '@/components/DialogForm.vue'
import DialogForm from '@/components/DialogForm.vue'

export interface Offering {
    id: number
    farmer: {
        id: number
        name: string
    }
    variety: {
        id: number
        name: string
        vegetable: string
    }
    image_url: string
    weight_kg: string
    asking_price: number
    expiration_date: string
    days_until_expiration: string
    status: string
    created_at_human: string
}

interface Props {
    summary?: {
        total_available: number
        total_archived: number
        expiring_this_week: number
        total_value: number
    }
    filters: {
        status: string | null
        page: number
    }
    offerings?: {
        data: Offering[]
        current_page:number
        last_page: number
        per_page: number
        total: number
    }
    varietyOptions?: VarietyOptionsByCategory
}

const props = defineProps<Props>()

const breadcrumbs = [
    { title: 'Farmer', href: farmer.garden.index().url },
    { title: 'Garden', href: farmer.garden.index().url },
]

const formOpen = ref(false)
const archiveOpen = ref(false)
const deleteOpen = ref(false)
const selectedOffering = ref<Offering | null>(null)

const activeTab = computed(() => props.filters.status || 'available')
const isLoadingVarieties = computed(() => !props.varietyOptions)

// Form for creating/updating offerings
const offeringForm = useForm({
    variety_id: '',
    weight_kg: '',
    asking_price: '',
    expiration_date: '',
    image: null as File | null,
})

// Form for archive action
const archiveForm = useForm({})

// Form for delete action  
const deleteForm = useForm({})

function handleTabChange(value: string) {
    router.visit(farmer.garden.index().url, {
        data: { 
            status: value === 'available' ? undefined : value,
            page: 1,
        },
        preserveScroll: true,
        preserveState: true,
        only: ['offerings', 'filters'],
    })
}

// CRUD handlers
function openCreate() {
    createForm.reset()
    createForm.clearErrors()
    createDialogOpen.value = true
}

function openEdit(offering: Offering) {
    selectedOffering.value = offering
    // Map offering to form - don't include variety_id for edits
    offeringForm.weight_kg = offering.weight_kg.toString()
    offeringForm.asking_price = offering.asking_price.toString()
    offeringForm.expiration_date = new Date(offering.expiration_date).toISOString().split('T')[0]
    offeringForm.image = null
    offeringForm.clearErrors()
    formOpen.value = true
}

function openArchive(offering: Offering) {
    selectedOffering.value = offering
    archiveOpen.value = true
}

function openDelete(offering: Offering) {
    selectedOffering.value = offering
    deleteOpen.value = true
}

function handleSubmit() {
    const url = selectedOffering.value
        ? farmer.garden.update(selectedOffering.value.id).url
        : farmer.garden.store().url

    const method = selectedOffering.value ? 'put' : 'post'

    offeringForm
        .transform((data) => {
            // Only include variety_id for create, not update
            if (selectedOffering.value) {
                const { variety_id, ...rest } = data
                return rest
            }
            return data
        })
        [method](url, {
            onSuccess: () => {
                formOpen.value = false
                offeringForm.reset()
            },
        })
}

function handleArchive() {
    if (!selectedOffering.value) return

    archiveForm.post(farmer.garden.archive(selectedOffering.value.id).url, {
        onSuccess: () => {
            archiveOpen.value = false
            selectedOffering.value = null
        },
    })
}

function handleDelete() {
    if (!selectedOffering.value) return

    deleteForm.delete(farmer.garden.destroy(selectedOffering.value.id).url, {
        onSuccess: () => {
            deleteOpen.value = false
            selectedOffering.value = null
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
        only: ['offerings', 'filters'],
    })
}

function handleSearch(query: string) {
    router.visit(farmer.garden.index().url, {
        data: {
            search: query || undefined,
            status: props.filters.status || undefined,
            page: 1,
        },
        preserveScroll: true,
        only: ['offerings', 'filters'],
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
                    Post Offering
                </Button>
            </div>

            <!-- Summary Cards -->
            <Deferred data="summary">
                <template #fallback>
                    <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-2">
                        <Skeleton v-for="i in 4" :key="i" class="h-34"/>
                    </div>
                </template>

                <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-2">
                    <LargeCard 
                        title="Available"
                        :value="summary?.total_available"
                        subtext="all available offers"
                        :icon="Wheat"
                        card-class="from-emerald-50 to-green-50 dark:from-emerald-950/20 dark:to-green-950/20"
                    />

                    <LargeCard 
                        title="Archived"
                        :value="summary?.total_archived"
                        subtext="all archived offers"
                        :icon="Archive"
                        card-class="from-zinc-100 to-zinc-100 dark:from-zinc-950/20 dark:to-zinc-950/20"
                    />

                    <LargeCard 
                        title="Expiring"
                        :value="summary?.expiring_this_week"
                        subtext="expiring this week"
                        :icon="CalendarClock"
                        card-class="from-amber-50 to-orange-50 dark:from-emerald-950/20 dark:to-green-950/20"
                    />

                    <LargeCard 
                        title="Total Value"
                        :value="summary?.total_value"
                        subtext="all available offers"
                        :icon="Weight"
                        card-class="from-emerald-50 to-green-50 dark:from-emerald-950/20 dark:to-green-950/20"
                    />
                </div>
            </Deferred>

            <!-- Status Filter Tabs -->
            <div class="rounded-xl border-2 bg-card p-1 md:w-1/3">
                <Tabs :model-value="activeTab" @update:model-value="(v) => handleTabChange(String(v))">
                    <TabsList class="w-full grid-cols-2">
                        <TabsTrigger 
                            value="available" 
                            class="data-[state=active]:bg-primary data-[state=active]:text-primary-foreground"
                        >
                            Available
                        </TabsTrigger>
                        <TabsTrigger
                            value="archived"
                            class="data-[state=active]:bg-primary data-[state=active]:text-primary-foreground"
                        >
                            Archived
                        </TabsTrigger>
                    </TabsList>
                </Tabs>
            </div>

            <!-- Search -->
            <InputGroup>
                <InputGroupInput placeholder="Search..." />
                <InputGroupAddon>
                    <Search />
                </InputGroupAddon>
            </InputGroup>

            <!-- Plantings Grid -->
             <Deferred data="offerings">
                <template #fallback>
                    <div>Loading offerings...</div>
                </template>

                <EmptyState 
                    v-if="!offerings?.data.length"
                    title="No Posted Offers Yet"
                    description="You haven't created a post yet. Get started by creating your first post."
                    :icon="Sprout"
                    button="Create Post"
                />

                <div v-else class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                    <GardenCard 
                        v-for="offering in offerings.data"
                        :key="offering.id"
                        :offering="offering"
                        @open-edit="$emit('open-edit', $event)"
                        @open-archive="$emit('open-archive', $event)"
                        @open-delete="$emit('open-delete', $event)"
                    />
                </div>
             </Deferred>
        </div>
    </AppLayout>

    </DialogForm>

    <!-- Modals -->

    <ArchivePlantingDialog
        :open="archiveOpen"
        :planting="selectedOffering"
        :is-processing="archiveForm.processing"
        @update:open="archiveOpen = $event"
        @confirm="handleArchive"
    />

    <DeletePlantingDialog
        :open="deleteOpen"
        :planting="selectedOffering"
        :is-processing="deleteForm.processing"
        @update:open="deleteOpen = $event"
        @confirm="handleDelete"
    />
</template>
