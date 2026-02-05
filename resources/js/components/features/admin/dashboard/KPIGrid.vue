<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { 
    Users, 
    Sprout, 
    Clock, 
    Store, 
    MessageSquare, 
    Gem, 
    TrendingUp,
    TrendingDown,
    Minus,
    AlertTriangle
} from 'lucide-vue-next'

interface KPIValue {
    value: number
    change?: number
    trend?: 'up' | 'down' | 'neutral'
    label?: string
}

interface KPIs {
    farmers: {
        total_farmers: KPIValue
        total_active_plantings: KPIValue
        harvesting_soon: KPIValue
        average_plantings_per_farmer: KPIValue
    }
    dealers: {
        total_dealers: KPIValue
        active_this_week: KPIValue
        total_conversations: KPIValue
        new_this_month: KPIValue
    }
    varieties: {
        total_varieties: KPIValue
        price_updates_week: KPIValue
        needs_attention: KPIValue
        average_harvest_time: KPIValue
    }
    system: {
        total_users: KPIValue
        active_conversations: KPIValue
        messages_sent: KPIValue
    }
}

defineProps<{
    kpis: KPIs
}>()

function getTrendIcon(trend?: string) {
    if (trend === 'up') return TrendingUp
    if (trend === 'down') return TrendingDown
    return Minus
}

function getTrendColor(trend?: string) {
    if (trend === 'up') return 'text-green-600 dark:text-green-500'
    if (trend === 'down') return 'text-red-600 dark:text-red-500'
    return 'text-muted-foreground'
}

function formatChange(change?: number): string {
    if (change === undefined) return ''
    const sign = change > 0 ? '+' : ''
    return `${sign}${change}%`
}
</script>

<template>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Farmers Section -->
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
                <div class="flex items-baseline justify-between">
                    <p class="text-2xl font-bold">{{ kpis.farmers.total_farmers.value }}</p>
                    <div v-if="kpis.farmers.total_farmers.change !== undefined" class="flex items-center gap-1">
                        <component 
                            :is="getTrendIcon(kpis.farmers.total_farmers.trend)" 
                            class="size-3"
                            :class="getTrendColor(kpis.farmers.total_farmers.trend)"
                        />
                        <span 
                            class="text-xs font-medium"
                            :class="getTrendColor(kpis.farmers.total_farmers.trend)"
                        >
                            {{ formatChange(kpis.farmers.total_farmers.change) }}
                        </span>
                    </div>
                </div>
                <p class="mt-1 text-xs text-muted-foreground">Approved farmers</p>
            </CardContent>
        </Card>

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
                <div class="flex items-baseline justify-between">
                    <p class="text-2xl font-bold">{{ kpis.farmers.total_active_plantings.value }}</p>
                    <div v-if="kpis.farmers.total_active_plantings.change !== undefined" class="flex items-center gap-1">
                        <component 
                            :is="getTrendIcon(kpis.farmers.total_active_plantings.trend)" 
                            class="size-3"
                            :class="getTrendColor(kpis.farmers.total_active_plantings.trend)"
                        />
                        <span 
                            class="text-xs font-medium"
                            :class="getTrendColor(kpis.farmers.total_active_plantings.trend)"
                        >
                            {{ formatChange(kpis.farmers.total_active_plantings.change) }}
                        </span>
                    </div>
                </div>
                <p class="mt-1 text-xs text-muted-foreground">Currently growing</p>
            </CardContent>
        </Card>

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
                    {{ kpis.farmers.harvesting_soon.value }}
                </p>
                <p class="mt-1 text-xs text-muted-foreground">{{ kpis.farmers.harvesting_soon.label }}</p>
            </CardContent>
        </Card>

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
                <p class="text-2xl font-bold">{{ kpis.farmers.average_plantings_per_farmer.value }}</p>
                <p class="mt-1 text-xs text-muted-foreground">Per farmer</p>
            </CardContent>
        </Card>

        <!-- Dealers Section -->
        <Card>
            <CardHeader class="pb-2">
                <div class="flex items-center justify-between">
                    <CardTitle class="text-sm font-medium text-muted-foreground">
                        Total Dealers
                    </CardTitle>
                    <Store class="size-4 text-primary" />
                </div>
            </CardHeader>
            <CardContent>
                <div class="flex items-baseline justify-between">
                    <p class="text-2xl font-bold">{{ kpis.dealers.total_dealers.value }}</p>
                    <div v-if="kpis.dealers.total_dealers.change !== undefined" class="flex items-center gap-1">
                        <component 
                            :is="getTrendIcon(kpis.dealers.total_dealers.trend)" 
                            class="size-3"
                            :class="getTrendColor(kpis.dealers.total_dealers.trend)"
                        />
                        <span 
                            class="text-xs font-medium"
                            :class="getTrendColor(kpis.dealers.total_dealers.trend)"
                        >
                            {{ formatChange(kpis.dealers.total_dealers.change) }}
                        </span>
                    </div>
                </div>
                <p class="mt-1 text-xs text-muted-foreground">Approved dealers</p>
            </CardContent>
        </Card>

        <Card>
            <CardHeader class="pb-2">
                <div class="flex items-center justify-between">
                    <CardTitle class="text-sm font-medium text-muted-foreground">
                        Active This Week
                    </CardTitle>
                    <Users class="size-4 text-green-500" />
                </div>
            </CardHeader>
            <CardContent>
                <p class="text-2xl font-bold text-green-600 dark:text-green-500">
                    {{ kpis.dealers.active_this_week.value }}
                </p>
                <p class="mt-1 text-xs text-muted-foreground">{{ kpis.dealers.active_this_week.label }}</p>
            </CardContent>
        </Card>

        <Card>
            <CardHeader class="pb-2">
                <div class="flex items-center justify-between">
                    <CardTitle class="text-sm font-medium text-muted-foreground">
                        Conversations
                    </CardTitle>
                    <MessageSquare class="size-4 text-primary" />
                </div>
            </CardHeader>
            <CardContent>
                <div class="flex items-baseline justify-between">
                    <p class="text-2xl font-bold">{{ kpis.dealers.total_conversations.value }}</p>
                    <div v-if="kpis.dealers.total_conversations.change !== undefined" class="flex items-center gap-1">
                        <component 
                            :is="getTrendIcon(kpis.dealers.total_conversations.trend)" 
                            class="size-3"
                            :class="getTrendColor(kpis.dealers.total_conversations.trend)"
                        />
                        <span 
                            class="text-xs font-medium"
                            :class="getTrendColor(kpis.dealers.total_conversations.trend)"
                        >
                            {{ formatChange(kpis.dealers.total_conversations.change) }}
                        </span>
                    </div>
                </div>
                <p class="mt-1 text-xs text-muted-foreground">All time</p>
            </CardContent>
        </Card>

        <Card>
            <CardHeader class="pb-2">
                <div class="flex items-center justify-between">
                    <CardTitle class="text-sm font-medium text-muted-foreground">
                        New This Month
                    </CardTitle>
                    <TrendingUp class="size-4 text-primary" />
                </div>
            </CardHeader>
            <CardContent>
                <p class="text-2xl font-bold">{{ kpis.dealers.new_this_month.value }}</p>
                <p class="mt-1 text-xs text-muted-foreground">New dealers</p>
            </CardContent>
        </Card>

        <!-- Varieties Section -->
        <Card>
            <CardHeader class="pb-2">
                <div class="flex items-center justify-between">
                    <CardTitle class="text-sm font-medium text-muted-foreground">
                        Total Varieties
                    </CardTitle>
                    <Gem class="size-4 text-primary" />
                </div>
            </CardHeader>
            <CardContent>
                <p class="text-2xl font-bold">{{ kpis.varieties.total_varieties.value }}</p>
                <p class="mt-1 text-xs text-muted-foreground">Available varieties</p>
            </CardContent>
        </Card>

        <Card>
            <CardHeader class="pb-2">
                <div class="flex items-center justify-between">
                    <CardTitle class="text-sm font-medium text-muted-foreground">
                        Price Updates
                    </CardTitle>
                    <TrendingUp class="size-4 text-blue-500" />
                </div>
            </CardHeader>
            <CardContent>
                <p class="text-2xl font-bold text-blue-600 dark:text-blue-500">
                    {{ kpis.varieties.price_updates_week.value }}
                </p>
                <p class="mt-1 text-xs text-muted-foreground">{{ kpis.varieties.price_updates_week.label }}</p>
            </CardContent>
        </Card>

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
                <p class="text-2xl font-bold text-orange-600 dark:text-orange-500">
                    {{ kpis.varieties.needs_attention.value }}
                </p>
                <p class="mt-1 text-xs text-muted-foreground">{{ kpis.varieties.needs_attention.label }}</p>
            </CardContent>
        </Card>

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
                <div class="flex items-baseline gap-1">
                    <p class="text-2xl font-bold">{{ kpis.varieties.average_harvest_time.value }}</p>
                    <span class="text-sm text-muted-foreground">{{ kpis.varieties.average_harvest_time.label }}</span>
                </div>
                <p class="mt-1 text-xs text-muted-foreground">Average duration</p>
            </CardContent>
        </Card>

        <!-- System Section -->
        <Card>
            <CardHeader class="pb-2">
                <div class="flex items-center justify-between">
                    <CardTitle class="text-sm font-medium text-muted-foreground">
                        Total Users
                    </CardTitle>
                    <Users class="size-4 text-primary" />
                </div>
            </CardHeader>
            <CardContent>
                <div class="flex items-baseline justify-between">
                    <p class="text-2xl font-bold">{{ kpis.system.total_users.value }}</p>
                    <div v-if="kpis.system.total_users.change !== undefined" class="flex items-center gap-1">
                        <component 
                            :is="getTrendIcon(kpis.system.total_users.trend)" 
                            class="size-3"
                            :class="getTrendColor(kpis.system.total_users.trend)"
                        />
                        <span 
                            class="text-xs font-medium"
                            :class="getTrendColor(kpis.system.total_users.trend)"
                        >
                            {{ formatChange(kpis.system.total_users.change) }}
                        </span>
                    </div>
                </div>
                <p class="mt-1 text-xs text-muted-foreground">Platform users</p>
            </CardContent>
        </Card>

        <Card>
            <CardHeader class="pb-2">
                <div class="flex items-center justify-between">
                    <CardTitle class="text-sm font-medium text-muted-foreground">
                        Active Conversations
                    </CardTitle>
                    <MessageSquare class="size-4 text-green-500" />
                </div>
            </CardHeader>
            <CardContent>
                <p class="text-2xl font-bold text-green-600 dark:text-green-500">
                    {{ kpis.system.active_conversations.value }}
                </p>
                <p class="mt-1 text-xs text-muted-foreground">{{ kpis.system.active_conversations.label }}</p>
            </CardContent>
        </Card>

        <Card>
            <CardHeader class="pb-2">
                <div class="flex items-center justify-between">
                    <CardTitle class="text-sm font-medium text-muted-foreground">
                        Messages Sent
                    </CardTitle>
                    <MessageSquare class="size-4 text-primary" />
                </div>
            </CardHeader>
            <CardContent>
                <div class="flex items-baseline justify-between">
                    <p class="text-2xl font-bold">{{ kpis.system.messages_sent.value }}</p>
                    <div v-if="kpis.system.messages_sent.change !== undefined" class="flex items-center gap-1">
                        <component 
                            :is="getTrendIcon(kpis.system.messages_sent.trend)" 
                            class="size-3"
                            :class="getTrendColor(kpis.system.messages_sent.trend)"
                        />
                        <span 
                            class="text-xs font-medium"
                            :class="getTrendColor(kpis.system.messages_sent.trend)"
                        >
                            {{ formatChange(kpis.system.messages_sent.change) }}
                        </span>
                    </div>
                </div>
                <p class="mt-1 text-xs text-muted-foreground">Last 30 days</p>
            </CardContent>
        </Card>
    </div>
</template>
