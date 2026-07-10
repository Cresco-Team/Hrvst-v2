<script setup lang="ts">
// Assuming you are using Lucide icons or similar
import { Link } from '@inertiajs/vue3'
import type { Component } from 'vue'
import type { RouteDefinition } from '@/wayfinder'
import { Item, ItemContent, ItemDescription, ItemMedia, ItemTitle } from './ui/item'

withDefaults(
	defineProps<{
		href: RouteDefinition<'get'> | undefined
		title: string
		description: string
		icon: Component
		colorClasses?: string
		iconClasses?: string
	}>(),
	{
		colorClasses: 'from-green-500/10 to-emerald-500/10',
		iconClasses: 'text-green-600 dark:text-green-500',
	},
)
</script>

<template>
    <Link :href="href">
        <Item
            variant="outline"
            :class="['bg-linear-to-br hover:shadow-md transition-all', colorClasses]"
        >
            <ItemMedia
                variant="icon"
                class="bg-background/80 p-2 backdrop-blur-sm"
            >
                <component
                    :is="icon"
                    :class="iconClasses"
                />
            </ItemMedia>
            <ItemContent>
                <ItemTitle>{{ title }}</ItemTitle>
                <ItemDescription>{{ description }}</ItemDescription>
            </ItemContent>
        </Item>
    </Link>
</template>