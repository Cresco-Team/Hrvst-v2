<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import Heading from '@/components/Heading.vue'
import DealerTable from '@/components/features/admin/tables/DealerTable.vue'
import admin from '@/routes/admin'
import AppLayout from '@/layouts/AppLayout.vue'
import { BreadcrumbItem } from '@/types'
import LargeCard from '@/components/shared/cards/LargeCard.vue'
import { MessageSquare, TrendingUp, UserCheck, Users } from 'lucide-vue-next'

interface Dealer {
    id: number
    user: {
        id: number
        name: string
        email: string
        phone_number: string
        user_image: string | null
    }
    activity: {
        total_conversations: number
        active_conversations: number
        last_activity_at: string | null
        last_activity_human: string | null
        status: 'active' | 'moderate' | 'inactive'
    }
    document_image: string | null
    joined_at: string
    joined_at_human: string
}

interface PaginatedData {
    data: Dealer[]
    current_page: number
    last_page: number
    per_page: number
    total: number
}

interface Summary {
    total_dealers: number
    active_this_week: number
    total_conversations: number
    new_this_month: number
}

defineProps<{
    dealers: PaginatedData
    summary: Summary
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: admin.dashboard().url },
    { title: 'Dealers', href: admin.dealers.index().url },
]

function handleViewDealer(dealer: Dealer) {
    router.visit(admin.dealers.show(dealer.id))
}

function handlePageChange(page: number) {
    router.visit(admin.dealers.index(), {
        data: { page },
        preserveState: true,
        preserveScroll: true,
    })
}
</script>

<template>
    <Head title="Dealers" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-col gap-6 p-4 lg:p-6">

            <!-- Header -->
            <div class="flex items-end justify-between">
                <Heading
                    title="Dealers"
                    description="Manage approved dealers and their activity metrics"
                />
            </div>

            <!-- Summary Cards -->
            <div v-if="summary" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-2">
                <LargeCard 
                    title="Registered Dealers"
                    :value="summary.total_dealers"
                    subtext="approved dealers"
                    :icon="Users"
                    card-class="col-span-1 bg-linear-to-br from-cyan-500/10 via-sky-500/10 to-blue-500/30"
                />
                <LargeCard 
                    title="Active Dealers"
                    :value="summary.active_this_week"
                    subtext="active this week"
                    :icon="UserCheck"
                    card-class="col-span-1 bg-linear-to-br from-cyan-500/10 via-sky-500/10 to-blue-500/30"
                />
                <LargeCard 
                    title="Total Conversations"
                    :value="summary.total_conversations"
                    subtext="all time"
                    :icon="MessageSquare"
                    card-class="col-span-1 bg-linear-to-br from-cyan-500/10 via-sky-500/10 to-blue-500/30"
                />
                <LargeCard 
                    title="New Dealers"
                    :value="summary.new_this_month"
                    subtext="registered this month"
                    :icon="TrendingUp"
                    card-class="col-span-1 bg-linear-to-br from-cyan-500/10 via-sky-500/10 to-blue-500/30"
                />
            </div>

            <DealerTable
                :dealers="dealers"
                @view-dealer="handleViewDealer"
                @page-change="handlePageChange"
            />
        </div>
    </AppLayout>
</template>
