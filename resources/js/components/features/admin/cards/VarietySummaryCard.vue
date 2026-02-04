<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Leaf, Package, Clock, TrendingUp, AlertTriangle } from 'lucide-vue-next'

interface Summary {
    total_varieties: number
    total_vegetables: number
    average_weeks_to_harvest: number
    price_stats: {
        updated_week: number
        updated_month: number
        stale: number
        no_price: number
    }
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

        <!-- Price Freshness Stats -->
        <Card>
            <CardHeader class="pb-2">
                <div class="flex items-center justify-between">
                    <CardTitle class="text-sm font-medium text-muted-foreground">
                        Price Updates
                    </CardTitle>
                    <TrendingUp class="size-4 text-primary" />
                </div>
            </CardHeader>
            <CardContent>
                <div class="flex items-baseline gap-2">
                    <p class="text-2xl font-bold">{{ summary.price_stats.updated_week }}</p>
                    <span class="text-xs text-muted-foreground">this week</span>
                </div>
                <div class="flex flex-wrap gap-1.5 mt-2">
                    <Badge variant="secondary" class="text-xs gap-1">
                        <div class="size-1.5 rounded-full bg-blue-500"></div>
                        {{ summary.price_stats.updated_month }} this month
                    </Badge>
                </div>
            </CardContent>
        </Card>

        <!-- Attention Needed -->
        <Card>
            <CardHeader class="pb-2">
                <div class="flex items-center justify-between">
                    <CardTitle class="text-sm font-medium text-muted-foreground">
                        Needs Attention
                    </CardTitle>
                    <AlertTriangle class="size-4 text-orange-500" />
                </div>
            </CardHeader>
            <CardContent>
                <div class="flex items-baseline gap-2">
                    <p class="text-2xl font-bold text-orange-600 dark:text-orange-500">
                        {{ summary.price_stats.stale + summary.price_stats.no_price }}
                    </p>
                    <span class="text-xs text-muted-foreground">varieties</span>
                </div>
                <div class="flex flex-wrap gap-1.5 mt-2">
                    <Badge variant="outline" class="text-xs gap-1 border-orange-200 dark:border-orange-800">
                        {{ summary.price_stats.stale }} stale prices
                    </Badge>
                    <Badge variant="outline" class="text-xs gap-1 border-red-200 dark:border-red-800">
                        {{ summary.price_stats.no_price }} no prices
                    </Badge>
                </div>
            </CardContent>
        </Card>
    </div>
</template>