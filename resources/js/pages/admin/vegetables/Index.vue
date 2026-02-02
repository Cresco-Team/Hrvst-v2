<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import admin from '@/routes/admin';
import { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Vegetables',
        href: admin.vegetables.index().url,
    }
]

</script>

<template>
    <Head title="Vegetables" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-autorounded-xl p-4">

        </div>
    </AppLayout>
</template>