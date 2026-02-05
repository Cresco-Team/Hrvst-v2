<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { TrendingUp, TrendingDown, Minus, AlertTriangle, BarChart3, Activity } from 'lucide-vue-next'

interface TrendingVariety {
    variety_id: number
    name: string
    count: number
    change_percent: number
    trend: 'up' | 'down' | 'neutral'
}

interface SupplyGap {
    category_id: number
    category_name: string
    active_count: number
}

interface ForecastWeek {
    week: string
    date_range: string
    total_weight: number
    [category: string]: string | number
}

interface QuickStats {
    total_active_plantings: number
    harvesting_this_week: number
    new_listings_today: number
}

interface Insights {
    trending: TrendingVariety[]
    supply_gaps: SupplyGap[]
    harvest_forecast: ForecastWeek[]
    stats: QuickStats
}

defineProps<{
    insights: Insights
}>()

function getTrendIcon(trend: string) {
    if (trend === 'up') return TrendingUp
    if (trend === 'down') return TrendingDown
    return Minus
}

function getTrendColor(trend: string) {
    if (trend === 'up') return 'text-green-600 dark:text-green-500'
    if (trend === 'down') return 'text-red-600 dark:text-red-500'
    return 'text-muted-foreground'
}

function formatChange(change: number): string {
    const sign = change > 0 ? '+' : ''
    return `${sign}${change}%`
}
</script>

<template>
    <div class="space-y-4">
        <!-- Quick Stats -->
        <Card>
            <CardHeader class="pb-3">
                <div class="flex items-center gap-2">
                    <Activity class="size-4 text-primary" />
                    <CardTitle class="text-base">Market Overview</CardTitle>
                </div>
            </CardHeader>
            <CardContent class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-muted-foreground">Total Active</span>
                    <span class="text-lg font-bold">{{ insights.stats.total_active_plantings }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-muted-foreground">Harvesting This Week</span>
                    <span class="text-lg font-bold text-orange-600 dark:text-orange-500">
                        {{ insights.stats.harvesting_this_week }}
                    </span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-muted-foreground">New Today</span>
                    <span class="text-lg font-bold text-green-600 dark:text-green-500">
                        {{ insights.stats.new_listings_today }}
                    </span>
                </div>
            </CardContent>
        </Card>

        <!-- Trending Varieties -->
        <Card v-if="insights.trending.length > 0">
            <CardHeader class="pb-3">
                <div class="flex items-center gap-2">
                    <TrendingUp class="size-4 text-primary" />
                    <CardTitle class="text-base">Trending Varieties</CardTitle>
                </div>
            </CardHeader>
            <CardContent class="space-y-2">
                <div
                    v-for="(variety, index) in insights.trending.slice(0, 5)"
                    :key="variety.variety_id"
                    class="flex items-start justify-between gap-2 rounded-lg border p-2.5 transition-colors hover:bg-muted/50"
                >
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <span class="font-mono text-xs text-muted-foreground">
                                #{{ index + 1 }}
                            </span>
                            <span class="text-sm font-medium">{{ variety.name }}</span>
                        </div>
                        <p class="mt-0.5 text-xs text-muted-foreground">
                            {{ variety.count }} active plantings
                        </p>
                    </div>
                    <div class="flex items-center gap-1 shrink-0">
                        <component
                            :is="getTrendIcon(variety.trend)"
                            class="size-3"
                            :class="getTrendColor(variety.trend)"
                        />
                        <span
                            class="text-xs font-semibold"
                            :class="getTrendColor(variety.trend)"
                        >
                            {{ formatChange(variety.change_percent) }}
                        </span>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Supply Gaps -->
        <Card v-if="insights.supply_gaps.length > 0">
            <CardHeader class="pb-3">
                <div class="flex items-center gap-2">
                    <AlertTriangle class="size-4 text-orange-500" />
                    <CardTitle class="text-base">Supply Gaps</CardTitle>
                </div>
                <p class="text-xs text-muted-foreground">
                    Categories with low supply
                </p>
            </CardHeader>
            <CardContent class="space-y-2">
                <div
                    v-for="gap in insights.supply_gaps"
                    :key="gap.category_id"
                    class="flex items-center justify-between rounded-lg border border-orange-200 bg-orange-50 p-2.5 dark:border-orange-900 dark:bg-orange-950/20"
                >
                    <span class="text-sm font-medium">{{ gap.category_name }}</span>
                    <Badge variant="secondary" class="font-mono text-xs">
                        {{ gap.active_count }} active
                    </Badge>
                </div>
            </CardContent>
        </Card>

        <!-- Harvest Forecast -->
        <Card>
            <CardHeader class="pb-3">
                <div class="flex items-center gap-2">
                    <BarChart3 class="size-4 text-primary" />
                    <CardTitle class="text-base">4-Week Forecast</CardTitle>
                </div>
                <p class="text-xs text-muted-foreground">
                    Expected harvest volume
                </p>
            </CardHeader>
            <CardContent class="space-y-2">
                <div
                    v-for="week in insights.harvest_forecast"
                    :key="week.week"
                    class="rounded-lg border p-2.5"
                >
                    <div class="mb-1.5 flex items-baseline justify-between">
                        <span class="text-sm font-semibold">{{ week.week }}</span>
                        <span class="font-mono text-xs text-muted-foreground">
                            {{ week.date_range }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-muted-foreground">Total</span>
                        <span class="text-sm font-bold">
                            {{ week.total_weight.toLocaleString() }} kg
                        </span>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
