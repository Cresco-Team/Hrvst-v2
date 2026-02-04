<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Users, Sprout, Clock, TrendingUp } from 'lucide-vue-next'

interface Summary {
    total_farmers: number
    total_active_plantings: number
    harvesting_soon: number
    average_plantings_per_farmer: number
}

defineProps<{
    summary: Summary
}>()
</script>

<template>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
        <!-- Total Farmers -->
        <Card>
            <CardHeader class="pb-2">
                <div class="flex items-center justify-between">
                    <CardTitle class="text-sm font-medium text-muted-foreground">
                        Total Farmers
                    </CardTitle>
                    <Users class="size-4 text-primary" />
                </div>
            </CardHeader>
            <CardContent>
                <p class="text-2xl font-bold">{{ summary.total_farmers }}</p>
                <p class="text-xs text-muted-foreground mt-1">Approved farmers</p>
            </CardContent>
        </Card>

        <!-- Total Active Plantings -->
        <Card>
            <CardHeader class="pb-2">
                <div class="flex items-center justify-between">
                    <CardTitle class="text-sm font-medium text-muted-foreground">
                        Active Plantings
                    </CardTitle>
                    <Sprout class="size-4 text-primary" />
                </div>
            </CardHeader>
            <CardContent>
                <p class="text-2xl font-bold">{{ summary.total_active_plantings }}</p>
                <p class="text-xs text-muted-foreground mt-1">Currently growing</p>
            </CardContent>
        </Card>

        <!-- Harvesting Soon -->
        <Card>
            <CardHeader class="pb-2">
                <div class="flex items-center justify-between">
                    <CardTitle class="text-sm font-medium text-muted-foreground">
                        Harvesting Soon
                    </CardTitle>
                    <Clock class="size-4 text-orange-500" />
                </div>
            </CardHeader>
            <CardContent>
                <p class="text-2xl font-bold text-orange-600 dark:text-orange-500">
                    {{ summary.harvesting_soon }}
                </p>
                <p class="text-xs text-muted-foreground mt-1">Within 7 days</p>
            </CardContent>
        </Card>

        <!-- Average Plantings -->
        <Card>
            <CardHeader class="pb-2">
                <div class="flex items-center justify-between">
                    <CardTitle class="text-sm font-medium text-muted-foreground">
                        Avg. Plantings
                    </CardTitle>
                    <TrendingUp class="size-4 text-primary" />
                </div>
            </CardHeader>
            <CardContent>
                <p class="text-2xl font-bold">{{ summary.average_plantings_per_farmer }}</p>
                <p class="text-xs text-muted-foreground mt-1">Per farmer</p>
            </CardContent>
        </Card>
    </div>
</template>
