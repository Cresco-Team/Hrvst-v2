<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import { Users } from 'lucide-vue-next'
import AppShell from '@/components/AppShell.vue'
import AppHeader from '@/components/AppHeader.vue'
import AppContent from '@/components/AppContent.vue'
import Heading from '@/components/Heading.vue'
import FarmerSummaryCard from '@/components/features/admin/cards/FarmerSummaryCard.vue'
import admin from '@/routes/admin'
import FarmerTable from '@/components/features/admin/tables/FarmerTable.vue'

interface Planting {
    id: number
    variety: {
        id: number
        name: string
        category: string
        image_path: string
    }
    weight_kg: string
    date_planted: string
    expected_harvest_date: string
    days_until_harvest: number | null
    status_badge: string
}

interface Farmer {
    id: number
    user: {
        id: number
        name: string
        email: string
        phone_number: string
        user_image: string | null
    }
    location: {
        province: string
        municipality: string
        barangay: string
        coordinates: {
            lat: number
            lng: number
        }
    }
    farm_image: string | null
    active_plantings_count: number
    active_plantings: Planting[]
    joined_at: string
    joined_at_human: string
}

interface PaginatedData {
    data: Farmer[]
    current_page: number
    last_page: number
    per_page: number
    total: number
}

interface Summary {
    total_farmers: number
    total_active_plantings: number
    harvesting_soon: number
    average_plantings_per_farmer: number
}

defineProps<{
    farmers: PaginatedData
    summary: Summary
}>()

function handleViewFarmer(farmer: Farmer) {
    router.visit(admin.farmers.show(farmer.id))
}

function handlePageChange(page: number) {
    router.visit(admin.farmers.index(), {
        data: { page },
        preserveState: true,
        preserveScroll: true,
    })
}
</script>

<template>
    <Head title="Farmers" />

    <AppShell variant="header">
        <AppHeader
            :breadcrumbs="[
                { title: 'Admin', href: admin.dashboard().url },
                { title: 'Farmers', href: admin.farmers.index().url },
            ]"
        />
        <AppContent variant="header" class="p-6">
            <div class="flex flex-col gap-6">
                <div class="flex items-center gap-3">
                    <div
                        class="flex size-10 items-center justify-center rounded-lg bg-primary/10 text-primary"
                    >
                        <Users class="size-5" />
                    </div>
                    <Heading
                        title="Farmers"
                        description="Manage approved farmers and their active plantings"
                    />
                </div>

                <FarmerSummaryCard :summary="summary" />

                <FarmerTable
                    :farmers="farmers"
                    @view-farmer="handleViewFarmer"
                    @page-change="handlePageChange"
                />
            </div>
        </AppContent>
    </AppShell>
</template>
