<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Leaf, Layers, Sprout, BarChart3 } from 'lucide-vue-next'

interface CategoryStat {
    id: number
    name: string
    vegetables_count: number
}

interface Summary {
    total_vegetables: number
    total_categories: number
    total_varieties: number
    categories: CategoryStat[]
}

defineProps<{
    summary: Summary
}>()
</script>

<template>
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <!-- Total Vegetables -->
        <Card>
            <CardHeader class="pb-2">
                <div class="flex items-center justify-between">
                    <CardTitle class="text-sm font-medium text-muted-foreground">
                        Vegetables
                    </CardTitle>
                    <Sprout class="size-4 text-primary" />
                </div>
            </CardHeader>
            <CardContent>
                <p class="text-2xl font-bold">{{ summary.total_vegetables }}</p>
                <p class="text-xs text-muted-foreground mt-1">Total registered</p>
            </CardContent>
        </Card>

        <!-- Total Categories -->
        <Card>
            <CardHeader class="pb-2">
                <div class="flex items-center justify-between">
                    <CardTitle class="text-sm font-medium text-muted-foreground">
                        Categories
                    </CardTitle>
                    <Layers class="size-4 text-primary" />
                </div>
            </CardHeader>
            <CardContent>
                <p class="text-2xl font-bold">{{ summary.total_categories }}</p>
                <p class="text-xs text-muted-foreground mt-1">Active groups</p>
            </CardContent>
        </Card>

        <!-- Total Varieties -->
        <Card>
            <CardHeader class="pb-2">
                <div class="flex items-center justify-between">
                    <CardTitle class="text-sm font-medium text-muted-foreground">
                        Varieties
                    </CardTitle>
                    <Leaf class="size-4 text-primary" />
                </div>
            </CardHeader>
            <CardContent>
                <p class="text-2xl font-bold">{{ summary.total_varieties }}</p>
                <p class="text-xs text-muted-foreground mt-1">Across all vegetables</p>
            </CardContent>
        </Card>

        <!-- Category Breakdown -->
        <Card>
            <CardHeader class="pb-2">
                <div class="flex items-center justify-between">
                    <CardTitle class="text-sm font-medium text-muted-foreground">
                        Breakdown
                    </CardTitle>
                    <BarChart3 class="size-4 text-primary" />
                </div>
            </CardHeader>
            <CardContent>
                <div class="flex flex-wrap gap-1.5">
                    <Badge
                        v-for="cat in summary.categories"
                        :key="cat.id"
                        variant="secondary"
                        class="text-xs"
                    >
                        {{ cat.name }}
                        <span class="ml-1 font-bold">{{ cat.vegetables_count }}</span>
                    </Badge>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
