<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import { LayoutDashboard, TrendingUp, TrendingDown, Minus } from 'lucide-vue-next'
import AppShell from '@/components/AppShell.vue'
import AppHeader from '@/components/AppHeader.vue'
import AppContent from '@/components/AppContent.vue'
import PlantingsChart from '@/components/features/admin/dashboard/PlantingsChart.vue'
import HarvestForecast from '@/components/features/admin/dashboard/HarvestForecast.vue'
import ActivityChart from '@/components/features/admin/dashboard/ActivityChart.vue'
import VarietyDistribution from '@/components/features/admin/dashboard/VarietyDistribution.vue'
import QuickActions from '@/components/features/admin/dashboard/QuickActions.vue'
import { Skeleton } from '@/components/ui/skeleton'
import admin from '@/routes/admin'
import AppLayout from '@/layouts/AppLayout.vue'

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

interface TimelineData {
    date: string
    count: number
}

interface ForecastData {
    week: string
    date_range: string
    [category: string]: string | number
}

interface ActivityData {
    date: string
    messages: number
    conversations: number
}

interface DistributionData {
    name: string
    category: string
    value: number
}

interface Charts {
    plantings_timeline: TimelineData[]
    harvest_forecast: ForecastData[]
    conversation_activity: ActivityData[]
    variety_distribution: DistributionData[]
}

defineProps<{
    kpis: KPIs
    charts?: Charts
}>()

const breadcrumbs = [
    { title: 'Admin', href: admin.dashboard().url },
    { title: 'Dashboard', href: admin.dashboard().url },
]

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
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 lg:p-6">
            <!-- Page Title -->
            <div>
                <h1 class="text-3xl font-bold tracking-tight">Dashboard</h1>
                <p class="mt-1 text-sm text-muted-foreground">Platform overview and key metrics.</p>
            </div>

            <!-- Bento Grid Layout -->
            <div class="grid auto-rows-auto gap-4 md:grid-cols-6 lg:grid-cols-12">
                
                <!-- Hero KPI - Total Farmers (Large) -->
                <div class="group relative overflow-hidden rounded-xl border bg-linear-to-br from-blue-500/10 via-purple-500/10 to-pink-500/10 p-6 shadow-sm transition-all hover:shadow-md md:col-span-3 lg:col-span-4">
                    <div class="absolute -right-8 -top-8 size-32 rounded-full bg-blue-500/20 blur-3xl"></div>
                    <div class="relative space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-muted-foreground">Total Farmers</span>
                            <div 
                                v-if="kpis.farmers.total_farmers.change !== undefined"
                                class="flex items-center gap-1 rounded-full bg-background/80 px-2 py-1 backdrop-blur-sm"
                            >
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
                        <div class="flex items-baseline gap-2">
                            <p class="text-5xl font-bold">{{ kpis.farmers.total_farmers.value }}</p>
                            <span class="text-sm text-muted-foreground">approved</span>
                        </div>
                        <p class="text-xs text-muted-foreground">Active on the platform</p>
                    </div>
                </div>

                <!-- Hero KPI - Active Plantings (Large) -->
                <div class="group relative overflow-hidden rounded-xl border bg-linear-to-br from-green-500/10 via-emerald-500/10 to-teal-500/10 p-6 shadow-sm transition-all hover:shadow-md md:col-span-3 lg:col-span-4">
                    <div class="absolute -left-8 -bottom-8 size-32 rounded-full bg-green-500/20 blur-3xl"></div>
                    <div class="relative space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-muted-foreground">Active Plantings</span>
                            <div 
                                v-if="kpis.farmers.total_active_plantings.change !== undefined"
                                class="flex items-center gap-1 rounded-full bg-background/80 px-2 py-1 backdrop-blur-sm"
                            >
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
                        <div class="flex items-baseline gap-2">
                            <p class="text-5xl font-bold">{{ kpis.farmers.total_active_plantings.value }}</p>
                            <span class="text-sm text-muted-foreground">growing</span>
                        </div>
                        <p class="text-xs text-muted-foreground">Currently in cultivation</p>
                    </div>
                </div>

                <!-- Alert KPI - Harvesting Soon (Medium) -->
                <div class="relative overflow-hidden rounded-xl border border-orange-200 bg-linear-to-br from-orange-500/10 to-amber-500/10 p-5 shadow-sm transition-all hover:shadow-md dark:border-orange-900/50 md:col-span-3 lg:col-span-4">
                    <div class="space-y-2">
                        <span class="text-sm font-medium text-orange-700 dark:text-orange-400">Harvesting Soon</span>
                        <div class="flex items-baseline gap-2">
                            <p class="text-4xl font-bold text-orange-600 dark:text-orange-500">
                                {{ kpis.farmers.harvesting_soon.value }}
                            </p>
                        </div>
                        <p class="text-xs text-muted-foreground">{{ kpis.farmers.harvesting_soon.label }}</p>
                    </div>
                </div>

                <!-- Compact KPIs Row 1 -->
                <div class="rounded-xl border bg-card p-4 shadow-sm transition-all hover:shadow-md md:col-span-2 lg:col-span-3">
                    <div class="space-y-2">
                        <span class="text-xs font-medium text-muted-foreground">Total Dealers</span>
                        <div class="flex items-baseline justify-between">
                            <p class="text-2xl font-bold">{{ kpis.dealers.total_dealers.value }}</p>
                            <component 
                                v-if="kpis.dealers.total_dealers.trend"
                                :is="getTrendIcon(kpis.dealers.total_dealers.trend)" 
                                class="size-4"
                                :class="getTrendColor(kpis.dealers.total_dealers.trend)"
                            />
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border bg-card p-4 shadow-sm transition-all hover:shadow-md md:col-span-2 lg:col-span-3">
                    <div class="space-y-2">
                        <span class="text-xs font-medium text-muted-foreground">Active This Week</span>
                        <p class="text-2xl font-bold text-green-600 dark:text-green-500">
                            {{ kpis.dealers.active_this_week.value }}
                        </p>
                    </div>
                </div>

                <div class="rounded-xl border bg-card p-4 shadow-sm transition-all hover:shadow-md md:col-span-2 lg:col-span-3">
                    <div class="space-y-2">
                        <span class="text-xs font-medium text-muted-foreground">Conversations</span>
                        <div class="flex items-baseline justify-between">
                            <p class="text-2xl font-bold">{{ kpis.dealers.total_conversations.value }}</p>
                            <component 
                                v-if="kpis.dealers.total_conversations.trend"
                                :is="getTrendIcon(kpis.dealers.total_conversations.trend)" 
                                class="size-4"
                                :class="getTrendColor(kpis.dealers.total_conversations.trend)"
                            />
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border bg-card p-4 shadow-sm transition-all hover:shadow-md md:col-span-2 lg:col-span-3">
                    <div class="space-y-2">
                        <span class="text-xs font-medium text-muted-foreground">Total Varieties</span>
                        <p class="text-2xl font-bold">{{ kpis.varieties.total_varieties.value }}</p>
                    </div>
                </div>

                <!-- Compact KPIs Row 2 -->
                <div class="rounded-xl border bg-card p-4 shadow-sm transition-all hover:shadow-md md:col-span-2 lg:col-span-3">
                    <div class="space-y-2">
                        <span class="text-xs font-medium text-muted-foreground">Price Updates</span>
                        <p class="text-2xl font-bold text-blue-600 dark:text-blue-500">
                            {{ kpis.varieties.price_updates_week.value }}
                        </p>
                        <p class="text-xs text-muted-foreground">this week</p>
                    </div>
                </div>

                <div class="rounded-xl border border-orange-200 bg-card p-4 shadow-sm transition-all hover:shadow-md dark:border-orange-900/50 md:col-span-2 lg:col-span-3">
                    <div class="space-y-2">
                        <span class="text-xs font-medium text-orange-700 dark:text-orange-400">Needs Attention</span>
                        <p class="text-2xl font-bold text-orange-600 dark:text-orange-500">
                            {{ kpis.varieties.needs_attention.value }}
                        </p>
                        <p class="text-xs text-muted-foreground">stale prices</p>
                    </div>
                </div>

                <div class="rounded-xl border bg-card p-4 shadow-sm transition-all hover:shadow-md md:col-span-2 lg:col-span-3">
                    <div class="space-y-2">
                        <span class="text-xs font-medium text-muted-foreground">Total Users</span>
                        <div class="flex items-baseline justify-between">
                            <p class="text-2xl font-bold">{{ kpis.system.total_users.value }}</p>
                            <component 
                                v-if="kpis.system.total_users.trend"
                                :is="getTrendIcon(kpis.system.total_users.trend)" 
                                class="size-4"
                                :class="getTrendColor(kpis.system.total_users.trend)"
                            />
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border bg-card p-4 shadow-sm transition-all hover:shadow-md md:col-span-2 lg:col-span-3">
                    <div class="space-y-2">
                        <span class="text-xs font-medium text-muted-foreground">Messages Sent</span>
                        <div class="flex items-baseline justify-between">
                            <p class="text-2xl font-bold">{{ kpis.system.messages_sent.value }}</p>
                            <component 
                                v-if="kpis.system.messages_sent.trend"
                                :is="getTrendIcon(kpis.system.messages_sent.trend)" 
                                class="size-4"
                                :class="getTrendColor(kpis.system.messages_sent.trend)"
                            />
                        </div>
                        <p class="text-xs text-muted-foreground">last 30 days</p>
                    </div>
                </div>

                <!-- Charts Section -->
                <template v-if="charts">
                    <!-- Plantings Timeline (Wide) -->
                    <div class="md:col-span-6 lg:col-span-8">
                        <PlantingsChart :data="charts.plantings_timeline" />
                    </div>

                    <!-- Variety Distribution (Square) -->
                    <div class="md:col-span-6 lg:col-span-4">
                        <VarietyDistribution :data="charts.variety_distribution" />
                    </div>

                    <!-- Activity Chart (Wide) -->
                    <div class="md:col-span-6 lg:col-span-7">
                        <ActivityChart :data="charts.conversation_activity" />
                    </div>

                    <!-- Harvest Forecast (Medium) -->
                    <div class="md:col-span-6 lg:col-span-5">
                        <HarvestForecast :data="charts.harvest_forecast" />
                    </div>
                </template>

                <!-- Loading State for Charts -->
                <template v-else>
                    <div class="md:col-span-6 lg:col-span-8">
                        <Skeleton class="h-80 w-full rounded-xl" />
                    </div>
                    <div class="md:col-span-6 lg:col-span-4">
                        <Skeleton class="h-80 w-full rounded-xl" />
                    </div>
                    <div class="md:col-span-6 lg:col-span-7">
                        <Skeleton class="h-80 w-full rounded-xl" />
                    </div>
                    <div class="md:col-span-6 lg:col-span-5">
                        <Skeleton class="h-80 w-full rounded-xl" />
                    </div>
                </template>

                <!-- Quick Actions (Full Width) -->
                <div class="md:col-span-6 lg:col-span-12">
                    <QuickActions />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
