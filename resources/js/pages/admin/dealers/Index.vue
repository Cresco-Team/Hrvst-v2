<script setup lang="ts">
import { Deferred, Head, router } from '@inertiajs/vue3'
import Heading from '@/components/Heading.vue'
import DealerTable from '@/components/features/admin/tables/DealerTable.vue'
import admin from '@/routes/admin'
import AppLayout from '@/layouts/AppLayout.vue'
import { BreadcrumbItem } from '@/types'
import LargeCard from '@/components/shared/cards/LargeCard.vue'
import { Package, PackagePlus, PartyPopper, Users } from 'lucide-vue-next'
import { Skeleton } from '@/components/ui/skeleton'
import { Dealer, PaginatedData, Summary } from '@/types/admin/dealers'

defineProps<{
    summary: Summary
    dealers: PaginatedData
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
             <Deferred data="summary">
                <template #fallback>
                    <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-2">
                        <Skeleton v-for="i in 4" :key="i" class="h-33" />
                    </div>
                </template>

                <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-2">
                    <LargeCard 
                        title="Registered Dealers"
                        :value="summary.total_dealers"
                        subtext="approved dealers"
                        :icon="Users"
                        card-class="col-span-1 bg-linear-to-br from-cyan-500/10 via-sky-500/10 to-blue-500/30"
                    />
                    <LargeCard 
                        title="New Dealers"
                        :value="summary.new_dealers_this_month"
                        subtext="registered this month"
                        :icon="PartyPopper"
                        card-class="col-span-1 bg-linear-to-br from-cyan-500/10 via-sky-500/10 to-blue-500/30"
                    />
                    <LargeCard 
                        title="Total Request Posted"
                        :value="summary.total_requests"
                        subtext="all request posts"
                        :icon="Package"
                        card-class="col-span-1 bg-linear-to-br from-cyan-500/10 via-sky-500/10 to-blue-500/30"
                    />
                    <LargeCard 
                        title="New Posts"
                        :value="summary.new_requests_this_month"
                        subtext="posts this month"
                        :icon="PackagePlus"
                        card-class="col-span-1 bg-linear-to-br from-cyan-500/10 via-sky-500/10 to-blue-500/30"
                    />
                </div>
             </Deferred>
            

            <Deferred data="dealers">
                <template #fallback>
                    <div class="flex flex-col gap-4">
                        <Skeleton class="h-10 w-80" />
                        <div class="rounded-lg border p-4 space-y-3">
                            <Skeleton v-for="i in 5" :key="i" class="h-16 w-full" />
                        </div>
                    </div>
                </template>

                <DealerTable
                :dealers="dealers"
                @view-dealer="handleViewDealer"
                @page-change="handlePageChange"
            />
            </Deferred>
        </div>
    </AppLayout>
</template>
