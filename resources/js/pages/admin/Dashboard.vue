<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import StatCard from '@/components/shared/cards/StatCard.vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];
</script>

<template>
    <Head title="Admin Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <div class="grid auto-rows-min gap-4 md:grid-cols-3">
                <StatCard title="Average Volatility" value="#" />
                <StatCard title="Total Farmers" value="#" />
                <StatCard title="Total Dealers" value="#" />
            </div>
            <div class="grid auto-rows-min gap-2 md:grid-cols-2">
                <StatCard title="Hello" value="Nigga" />
                <StatCard title="Hello" value="Nigga" />
            </div>
        </div>
    </AppLayout>
</template>
