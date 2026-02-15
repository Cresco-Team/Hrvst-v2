<script setup lang="ts">
import { Deferred, Head, Link } from '@inertiajs/vue3'
import { TrendingUp, TrendingDown, Minus, Wheat, Tractor, Package } from 'lucide-vue-next'
import { Skeleton } from '@/components/ui/skeleton'
import admin from '@/routes/admin'
import AppLayout from '@/layouts/AppLayout.vue'
import LargeCard from '@/components/shared/cards/LargeCard.vue'
import SmallCard from '@/components/shared/cards/SmallCard.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Separator } from '@/components/ui/separator'
import { KPIs } from '@/types/admin/dashboard'
import Heading from '@/components/Heading.vue'
import QuickNavItem from '@/components/QuickNavItem.vue'

defineProps<{
    kpis?: KPIs
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
             <Heading 
                title="Dashboard"
                description="Overview of "
             />

            <Deferred data="kpis">
                <template #fallback>
                    <div class="grid md:grid-cols-3 gap-4">
                        <Skeleton v-for="i in 3" :key="i" class="h-33" />
                    </div>
                    <div class="grid md:grid-cols-10 gap-4">
                        <Skeleton v-for="i in 2" :key="i" class="h-21 col-span-5 lg:col-span-2" />
                        <Skeleton class="h-21 col-span-10 md:col-span-3 lg:col-span-2" />
                        <Skeleton class="h-21 col-span-5 md:col-span-4 lg:col-span-2" />
                        <Skeleton class="h-21 col-span-5 md:col-span-3 lg:col-span-2" />
                    </div>
                </template>

                <div class="grid md:grid-cols-3 gap-4">
                    <LargeCard 
                        title="Total Varieties"
                        subtext="recorded"
                        :value="kpis?.varieties.total_varieties.value"
                        :change="formatChange(kpis?.varieties.total_varieties.change)"
                        :trendColor="getTrendColor(kpis?.varieties.total_varieties.trend)"
                        cardClass="bg-linear-to-br from-orange-500/10 via-amber-500/10 to-yellow-500/30"
                    />

                    <LargeCard 
                        title="Total Farmers"
                        subtext="registered"
                        :value="kpis?.farmers.total_farmers.value"
                        :change="formatChange(kpis?.farmers.total_farmers.change)"
                        :badge="getTrendIcon(kpis?.farmers.total_farmers.trend)"
                        :trendColor="getTrendColor(kpis?.farmers.total_farmers.trend)"
                        cardClass="bg-linear-to-br from-lime-500/10 via-emerald-500/10 to-cyan-500/30"
                    />

                    <LargeCard 
                        title="Total Dealers"
                        subtext="registered"
                        :value="kpis?.dealers.total_dealers.value"
                        :change="formatChange(kpis?.dealers.total_dealers.change)"
                        :badge="getTrendIcon(kpis?.dealers.total_dealers.trend)"
                        :trendColor="getTrendColor(kpis?.dealers.total_dealers.trend)"
                        cardClass="bg-linear-to-br from-indigo-500/10 via-fuchsia-500/10 to-rose-500/30"
                    />
                </div>
                
                <div class="grid md:grid-cols-10 gap-4">
                    <SmallCard 
                        title="Total Farmer Offerings"
                        :value="kpis?.farmers.total_offerings.value"
                        cardClass="col-span-5 lg:col-span-2"
                    />
                    <SmallCard 
                        title="Total Dealer Requests"
                        :value="kpis?.dealers.total_requests.value"
                        cardClass="col-span-5 lg:col-span-2"
                    />

                    <SmallCard 
                        title="Updated This Week"
                        :value="kpis?.varieties.price_updates_week.value"
                        cardClass="col-span-10 md:col-span-3 lg:col-span-2"
                    />

                    <SmallCard 
                        title="Stale Vegetables"
                        :value="kpis?.varieties.needs_attention.value"
                        cardClass="col-span-5 md:col-span-4 lg:col-span-2"
                    />

                    <SmallCard 
                        title="Average Harvest Weeks"
                        :value="kpis?.varieties.average_harvest_time.value"
                        cardClass="col-span-5 md:col-span-3 lg:col-span-2"
                    />
                </div>
            </Deferred>

            <Card>
                <CardHeader>
                    <CardTitle>Quick Actions</CardTitle>
                    <CardDescription>Navigate to key sections</CardDescription>
                </CardHeader>
                <Separator />
                <CardContent class="grid md:grid-cols-3 gap-4">

                    <QuickNavItem 
                        :href="admin.vegetables_varieties.index()"
                        title="Vegetables"
                        description="Manage market"
                        :icon="Tractor"
                        colorClasses="from-green-500/10 to-emerald-500/10"
                        iconClasses="text-green-600 dark:text-green-500"
                    />

                    <QuickNavItem 
                        :href="admin.farmers.index()"
                        title="Farmers"
                        description="View all farmers"
                        :icon="Tractor"
                        colorClasses="from-amber-500/10 to-yellow-500/10"
                        iconClasses="text-yellow-600 dark:text-yellow-500"
                    />

                    <QuickNavItem 
                        :href="admin.dealers.index()"
                        title="Dealers"
                        description="Track Activity"
                        :icon="Package"
                        colorClasses="from-sky-500/10 to-blue-500/10"
                        iconClasses="text-blue-600 dark:text-blue-500"
                    />
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
