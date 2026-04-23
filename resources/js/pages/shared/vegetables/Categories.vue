<script setup lang='ts'>
import { Head, Link } from '@inertiajs/vue3'
import { ArrowRight, Leaf } from 'lucide-vue-next'
import Heading from '@/components/Heading.vue'
import { Item, ItemContent, ItemMedia, ItemTitle } from '@/components/ui/item'
import AppLayout from '@/layouts/AppLayout.vue'
import categories from '@/routes/categories'
import type { BreadcrumbItem, SharedCategoriesProps } from '@/types'

const props = defineProps<SharedCategoriesProps>()

const breadcrumbs: BreadcrumbItem[] = [
	{ title: 'Vegetable Categories', href: categories.index().url },
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

            <div class="grid grid-cols-2 gap-4 lg:grid-cols-3">
                <Link
                    v-for="category in props.categories"
                    :key="category.id"
                    :href="categories.vegetables.index({ category: category.slug }).url"
                    class="group"
                >
                    <Item variant="outline" class="transition-all hover:shadow-sm">
                        <ItemMedia variant="icon" class="bg-primary/10 text-primary">
                            <Leaf />
                        </ItemMedia>

                        <ItemContent class="flex-row justify-between overflow-hidden items-center">
                            <ItemTitle class="text-xs sm:text-sm">{{ category.name }}</ItemTitle>
                            <ArrowRight class="size-4 mr-5 hidden sm:flex  transition-transform duration-300 text-muted-foreground group-hover:translate-x-3 group-hover:text-foreground" />
                        </ItemContent>
                    </Item>
                </Link>
            </div>
        </div>
    </AppLayout>
</template>