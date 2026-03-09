<script setup lang="ts">
import { Deferred, Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import { Package, PackagePlus, PartyPopper, Users } from 'lucide-vue-next';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import DealerDetailSidebar from '@/components/admin/DealerDetailSidebar.vue';
import DealerTable from '@/components/features/admin/tables/DealerTable.vue';
import Heading from '@/components/Heading.vue';
import LargeCard from '@/components/shared/cards/LargeCard.vue';
import { Skeleton } from '@/components/ui/skeleton';
import AppLayout from '@/layouts/AppLayout.vue';
import admin from '@/routes/admin';
import type { BreadcrumbItem } from '@/types';
import type { Dealer, PaginatedData, Summary } from '@/types/admin/dealers';

const props = defineProps<{
    summary: Summary;
    dealers: PaginatedData;
    filters: { search: string | null };
}>();

const selectedDealer = ref<Dealer | null>(null);
const sidebarOpen = ref(false);
const loadingDealer = ref(false);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: admin.dashboard().url },
    { title: 'Dealers', href: admin.dealers.index().url },
];

const searchQuery = ref(props.filters?.search ?? '');

async function loadDealerDetails(dealerId: number) {
    loadingDealer.value = true;
    selectedDealer.value = null;
    sidebarOpen.value = true;
    try {
        const response = await axios.get(
            `/admin/dealers/api/${dealerId}/details`,
        );
        selectedDealer.value = response.data;
    } catch (error: any) {
        toast.error('Error loading dealer details', {
            description:
                error.response?.data?.error ||
                'Failed to load dealer information',
        });
        sidebarOpen.value = false;
    } finally {
        loadingDealer.value = false;
    }
}

function openDealerSidebar(dealerId: number) {
    loadDealerDetails(dealerId);
}

function handlePageChange(page: number) {
    router.visit(admin.dealers.index(), {
        data: { page, search: searchQuery.value || undefined },
        preserveState: true,
        preserveScroll: true,
    });
}

function handleSearch(query: string) {
    searchQuery.value = query;
    router.visit(admin.dealers.index().url, {
        data: { search: query || undefined },
        preserveState: true,
        preserveScroll: true,
        only: ['dealers', 'filters'],
    });
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
                    <div
                        class="grid gap-4 md:grid-cols-2 lg:grid-cols-4 lg:gap-2"
                    >
                        <Skeleton v-for="i in 4" :key="i" class="h-33" />
                    </div>
                </template>

                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4 lg:gap-2">
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
                        :value="summary.total_demands"
                        subtext="all request posts"
                        :icon="Package"
                        card-class="col-span-1 bg-linear-to-br from-cyan-500/10 via-sky-500/10 to-blue-500/30"
                    />
                    <LargeCard
                        title="New Posts"
                        :value="summary.new_demands_this_month"
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
                        <div class="space-y-3 rounded-lg border p-4">
                            <Skeleton
                                v-for="i in 5"
                                :key="i"
                                class="h-16 w-full"
                            />
                        </div>
                    </div>
                </template>

                <DealerTable
                    :dealers="dealers"
                    :search-query="searchQuery"
                    @view-dealer="openDealerSidebar($event.id)"
                    @page-change="handlePageChange"
                    @search="handleSearch"
                />
            </Deferred>
        </div>
    </AppLayout>

    <!-- Dealer Details Sidebar -->
    <DealerDetailSidebar
        :open="sidebarOpen"
        :dealer="selectedDealer"
        :loading="loadingDealer"
    />
</template>
