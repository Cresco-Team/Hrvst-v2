<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Leaf, Package, Clock } from 'lucide-vue-next'

interface Summary {
    total_varieties: number
    total_vegetables: number
    average_weeks_to_harvest: number
}

defineProps<{
    summary: Summary
}>()
</script>

<template>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <!-- Total Varieties -->
        <Card>
            <CardHeader class="pb-2">
                <div class="flex items-center justify-between">
                    <CardTitle class="text-sm font-medium text-muted-foreground">
                        Total Varieties
                    </CardTitle>
                    <Leaf class="size-4 text-primary" />
                </div>
            </CardHeader>
            <CardContent>
                <p class="text-2xl font-bold">{{ summary.total_varieties }}</p>
                <p class="text-xs text-muted-foreground mt-1">Available for planting</p>
            </CardContent>
        </Card>

        <!-- Total Vegetables -->
        <Card>
            <CardHeader class="pb-2">
                <div class="flex items-center justify-between">
                    <CardTitle class="text-sm font-medium text-muted-foreground">
                        Vegetable Types
                    </CardTitle>
                    <Package class="size-4 text-primary" />
                </div>
            </CardHeader>
            <CardContent>
                <p class="text-2xl font-bold">{{ summary.total_vegetables }}</p>
                <p class="text-xs text-muted-foreground mt-1">Distinct vegetables</p>
            </CardContent>
        </Card>

        <!-- Average Harvest Time -->
        <Card>
            <CardHeader class="pb-2">
                <div class="flex items-center justify-between">
                    <CardTitle class="text-sm font-medium text-muted-foreground">
                        Avg. Harvest Time
                    </CardTitle>
                    <Clock class="size-4 text-primary" />
                </div>
            </CardHeader>
            <CardContent>
                <p class="text-2xl font-bold">{{ summary.average_weeks_to_harvest }} weeks</p>
                <p class="text-xs text-muted-foreground mt-1">From planting to harvest</p>
            </CardContent>
        </Card>
    </div>
</template>