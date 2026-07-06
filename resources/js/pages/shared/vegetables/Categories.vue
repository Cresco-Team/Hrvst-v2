<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3'
import { ArrowRight, Leaf, Vegan } from 'lucide-vue-next'
import Heading from '@/components/Heading.vue'
import { Item, ItemContent, ItemMedia, ItemTitle } from '@/components/ui/item'
import AppLayout from '@/layouts/AppLayout.vue'
import { useCapitalize } from '@/lib/utils'
import { dashboard } from '@/routes'
import vegetables from '@/routes/vegetables'
import type { BreadcrumbItem, SharedCategoriesProps } from '@/types'

defineProps<SharedCategoriesProps>()

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: useCapitalize(usePage().props.auth.user.roles[0]),
        href: dashboard().url,
    },
    { title: 'Vegetable', href: vegetables.index().url },
]
</script>

<template>
    <Head title="Vegetable Categories" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 lg:p-6">
            <Heading
                title="Vegetable Categories"
                description="Select a Category to browse its vegetables and varieties."
            />

            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <Link
                    v-for="category in categories"
                    :key="category.id"
                    :href="vegetables.index().url"
                    :data="{ category: category.slug }"
                    class="group"
                >
                    <Item
                        variant="outline"
                        class="transition-all hover:shadow-sm bg-primary/10 hover:bg-primary/5 hover:border-l-4 hover:border-l-primary"
                    >
                        <ItemMedia
                            variant="icon"
                            class="bg-primary/10 text-primary"
                        >
                            <Vegan />
                        </ItemMedia>

                        <ItemContent
                            class="flex-row items-center justify-between overflow-hidden"
                        >
                            <ItemTitle class="text-xs sm:text-sm">{{
                                category.name
                            }}</ItemTitle>
                            <ArrowRight
                                class="mr-5 hidden size-4 text-muted-foreground transition-transform duration-300 group-hover:translate-x-3 group-hover:text-foreground sm:flex"
                            />
                        </ItemContent>
                    </Item>
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
