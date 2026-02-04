<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import { Store } from 'lucide-vue-next'
import AppShell from '@/components/AppShell.vue'
import AppHeader from '@/components/AppHeader.vue'
import AppContent from '@/components/AppContent.vue'
import Heading from '@/components/Heading.vue'
import DealerSummaryCard from '@/components/features/admin/cards/DealerSummaryCard.vue'
import DealerTable from '@/components/features/admin/tables/DealerTable.vue'
import admin from '@/routes/admin'

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

    <AppShell variant="header">
        <AppHeader
            :breadcrumbs="[
                { title: 'Admin', href: admin.dashboard().url },
                { title: 'Dealers', href: admin.dealers.index().url },
            ]"
        />
        <AppContent variant="header" class="p-6">
            <div class="flex flex-col gap-6">
                <div class="flex items-center gap-3">
                    <div
                        class="flex size-10 items-center justify-center rounded-lg bg-primary/10 text-primary"
                    >
                        <Store class="size-5" />
                    </div>
                    <Heading
                        title="Dealers"
                        description="Manage approved dealers and their activity metrics"
                    />
                </div>

                <DealerSummaryCard :summary="summary" />

                <DealerTable
                    :dealers="dealers"
                    @view-dealer="handleViewDealer"
                    @page-change="handlePageChange"
                />
            </div>
        </AppContent>
    </AppShell>
</template>
