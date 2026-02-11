<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import { TrendingUp, TrendingDown, Minus } from 'lucide-vue-next'
import PlantingsChart from '@/components/features/admin/dashboard/PlantingsChart.vue'
import HarvestForecast from '@/components/features/admin/dashboard/HarvestForecast.vue'
import ActivityChart from '@/components/features/admin/dashboard/ActivityChart.vue'
import VarietyDistribution from '@/components/features/admin/dashboard/VarietyDistribution.vue'
import QuickActions from '@/components/features/admin/dashboard/QuickActions.vue'
import { Skeleton } from '@/components/ui/skeleton'
import admin from '@/routes/admin'
import AppLayout from '@/layouts/AppLayout.vue'
import LargeCard from '@/components/shared/cards/LargeCard.vue'
import SmallCard from '@/components/shared/cards/SmallCard.vue'

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
                
                <!-- Hero KPI -->
                <LargeCard 
                    v-if="kpis.farmers.total_farmers"
                    title="Total Farmers"
                    subtext="active on the platform"
                    :value="kpis.farmers.total_farmers.value"
                    :change="formatChange(kpis.farmers.total_farmers.change)"
                    :badge="getTrendIcon(kpis.farmers.total_farmers.trend)"
                    :trendColor="getTrendColor(kpis.farmers.total_farmers.trend)"
                    cardClass="md:col-span-3 lg:col-span-4 bg-linear-to-br from-orange-500/10 via-amber-500/10 to-yellow-500/30"
                />

                <LargeCard 
                    v-if="kpis.farmers.total_active_plantings"
                    title="Active Plantings"
                    subtext="growing"
                    :value="kpis.farmers.total_active_plantings.value"
                    :change="formatChange(kpis.farmers.total_active_plantings.change)"
                    :badge="getTrendIcon(kpis.farmers.total_active_plantings.trend)"
                    :trendColor="getTrendColor(kpis.farmers.total_active_plantings.trend)"
                    cardClass="md:col-span-3 lg:col-span-4 bg-linear-to-br from-lime-500/10 via-emerald-500/10 to-cyan-500/30"
                />

                <LargeCard 
                    v-if="kpis.farmers.harvesting_soon"
                    title="Harvesting Soon"
                    subtext="this week"
                    :value="kpis.farmers.harvesting_soon.value"
                    :change="formatChange(kpis.farmers.harvesting_soon.change)"
                    :trendColor="getTrendColor(kpis.farmers.harvesting_soon.trend)"
                    cardClass="md:col-span-3 lg:col-span-4 bg-linear-to-br from-indigo-500/10 via-fuchsia-500/10 to-rose-500/30"
                />

                <!-- Compact KPIs Row 1 -->
                <SmallCard 
                    v-if="kpis.dealers.total_dealers"
                    title="Total Dealers"
                    :value="kpis.dealers.total_dealers.value"
                    :icon="getTrendIcon(kpis.dealers.total_dealers.trend)"
                    :iconClass="getTrendColor(kpis.dealers.total_dealers.trend)"
                    cardClass="md:col-span-2 lg:col-span-3" 
                />
                <SmallCard 
                    v-if="kpis.dealers.active_this_week"
                    title="Active This Week"
                    :value="kpis.dealers.active_this_week.value"
                    valueClass="text-green-600 dark:text-green-500"
                    cardClass="md:col-span-2 lg:col-span-3"
                />
                <SmallCard 
                    v-if="kpis.dealers.total_conversations"
                    title="Conversations"
                    :value="kpis.dealers.total_conversations.value"
                    :icon="getTrendIcon(kpis.dealers.total_conversations.trend)"
                    :iconClass="getTrendColor(kpis.dealers.total_conversations.trend)"
                    cardClass="md:col-span-2 lg:col-span-3"
                />
                <SmallCard 
                    v-if="kpis.varieties.total_varieties"
                    title="Total Varieties"
                    :value="kpis.varieties.total_varieties.value"
                    cardClass="md:col-span-2 lg:col-span-3"
                />

                <!-- Compact KPIs Row 2 -->
                <SmallCard 
                    v-if="kpis.varieties.total_varieties"
                    title="Price Updates"
                    :value="kpis.varieties.total_varieties.value"
                    subtext="this week"
                    cardClass="md:col-span-2 lg:col-span-3"
                />
                <SmallCard 
                    v-if="kpis.varieties.needs_attention"
                    title="Needs Attention"
                    :value="kpis.varieties.needs_attention.value"
                    valueClass="text-orange-600 dark:text-orange-500"
                    subtext="stale prices"
                    cardClass="md:col-span-2 lg:col-span-3 border border-orange-200 dark:border-orange-900/50"
                />
                <SmallCard 
                    v-if="kpis.system.total_users"
                    title="Total Users"
                    :value="kpis.system.total_users.value"
                    :icon="getTrendIcon(kpis.system.total_users.trend)"
                    :iconClass="getTrendColor(kpis.system.total_users.trend)"
                    cardClass="md:col-span-2 lg:col-span-3" 
                />
                <SmallCard 
                    v-if="kpis.system.messages_sent"
                    title="Messages Sent"
                    :value="kpis.system.messages_sent.value"
                    subtext="last 30 days"
                    :icon="getTrendIcon(kpis.system.messages_sent.trend)"
                    :iconClass="getTrendColor(kpis.system.messages_sent.trend)"
                    cardClass="md:col-span-2 lg:col-span-3" 
                />

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
