<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { ArrowRight, Layers, Leaf } from 'lucide-vue-next'
import Heading from '@/components/Heading.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import AppLayout from '@/layouts/AppLayout.vue'
import admin from '@/routes/admin'
import type { AdminCategoriesProps, BreadcrumbItem } from '@/types'

defineProps<AdminCategoriesProps>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: admin.dashboard().url },
    { title: 'Vegetables', href: admin.categories.index().url },
]
</script>

<template>
    <Head title="Vegetable Categories" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 lg:p-6">
            <Heading
                title="Vegetable Categories"
                description="Select a category to browse its vegetables and varieties."
            />

            <div
                v-if="categories.length === 0"
                class="flex items-center justify-center rounded-lg border border-dashed py-16 text-sm text-muted-foreground"
            >
                No categories found.
            </div>

            <div
                v-else
                class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4"
            >
                <Link
                    v-for="category in categories"
                    :key="category.id"
                    :href="admin.vegetables.index().url"
                    :data="{ category: category.slug }"
                    class="group"
                >
                    <Card
                        class="h-full cursor-pointer transition-all hover:-translate-y-0.5 hover:shadow-md"
                    >
                        <CardHeader class="pb-3">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-center gap-2.5">
                                    <div
                                        class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary/10"
                                    >
                                        <Leaf class="size-4 text-primary" />
                                    </div>
                                    <CardTitle class="text-base leading-tight">
                                        {{ category.name }}
                                    </CardTitle>
                                </div>
                                <ArrowRight
                                    class="mt-0.5 size-4 shrink-0 text-muted-foreground transition-transform group-hover:translate-x-0.5 group-hover:text-foreground"
                                />
                            </div>
                        </CardHeader>

                        <CardContent class="pt-0">
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex items-center gap-1.5 text-sm text-muted-foreground"
                                >
                                    <Leaf class="size-3.5 shrink-0" />
                                    <span
                                        class="font-medium text-foreground tabular-nums"
                                        >{{ category.vegetables_count }}</span
                                    >
                                    <span>{{
                                        category.vegetables_count === 1
                                            ? 'vegetable'
                                            : 'vegetables'
                                    }}</span>
                                </div>

                                <div class="h-3.5 w-px bg-border" />

                                <div
                                    class="flex items-center gap-1.5 text-sm text-muted-foreground"
                                >
                                    <Layers class="size-3.5 shrink-0" />
                                    <span
                                        class="font-medium text-foreground tabular-nums"
                                        >{{ category.varieties_count }}</span
                                    >
                                    <span>{{
                                        category.varieties_count === 1
                                            ? 'variety'
                                            : 'varieties'
                                    }}</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
