<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { Gem, Users, Store, Map } from 'lucide-vue-next'
import admin from '@/routes/admin'

const actions = [
    {
        title: 'Varieties',
        description: 'Manage products',
        icon: Gem,
        href: admin.vegetables_varieties.index(),
        gradient: 'from-purple-500/10 to-pink-500/10',
        iconColor: 'text-purple-600 dark:text-purple-500',
    },
    {
        title: 'Farmers',
        description: 'View all farmers',
        icon: Users,
        href: admin.farmers.index(),
        gradient: 'from-green-500/10 to-emerald-500/10',
        iconColor: 'text-green-600 dark:text-green-500',
    },
    {
        title: 'Dealers',
        description: 'Track activity',
        icon: Store,
        href: admin.dealers.index(),
        gradient: 'from-blue-500/10 to-cyan-500/10',
        iconColor: 'text-blue-600 dark:text-blue-500',
    },
    {
        title: 'Map View',
        description: 'Geographic data',
        icon: Map,
        href: admin.farmers.map.index(),
        gradient: 'from-orange-500/10 to-amber-500/10',
        iconColor: 'text-orange-600 dark:text-orange-500',
    },
]
</script>

<template>
    <div class="rounded-xl border bg-card shadow-sm">
        <div class="border-b bg-muted/30 p-4">
            <h3 class="font-semibold">Quick Actions</h3>
            <p class="text-xs text-muted-foreground">Navigate to key sections</p>
        </div>
        <div class="grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-4">
            <Link
                v-for="action in actions"
                :key="action.title"
                :href="action.href"
                class="group relative overflow-hidden rounded-lg border bg-gradient-to-br p-4 transition-all hover:shadow-md"
                :class="action.gradient"
            >
                <div class="flex items-start gap-3">
                    <div class="rounded-lg bg-background/80 p-2 backdrop-blur-sm">
                        <component :is="action.icon" class="size-4" :class="action.iconColor" />
                    </div>
                    <div class="flex-1">
                        <h4 class="font-medium leading-none">{{ action.title }}</h4>
                        <p class="mt-1 text-xs text-muted-foreground">{{ action.description }}</p>
                    </div>
                </div>
            </Link>
        </div>
    </div>
</template>
