<script setup lang="ts">
import { Deferred, Head } from '@inertiajs/vue3'
import {
    CategoryScale, Chart as ChartJS, type ChartOptions, Filler, Legend, LinearScale, LineElement, PointElement, Title, Tooltip,
} from 'chart.js'
import { AlertCircle, Heart, MapPin, Package, TrendingDown, TrendingUp, Minus } from 'lucide-vue-next'
import { computed } from 'vue'
import { Line } from 'vue-chartjs'
import Heading from '@/components/Heading.vue'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Skeleton } from '@/components/ui/skeleton'
import AppLayout from '@/layouts/AppLayout.vue'
import farmer from '@/routes/farmer'
import type { ShowVariety } from '@/types/shared/vegetables'

ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend,
    Filler,
)

interface Props {
    variety?: ShowVariety | null
}

const props = defineProps<Props>()

const breadcrumbs = computed(() => [
    { title: 'Farmer', href: farmer.supplies.index().url },
    { title: 'Vegetables', href: farmer.vegetables.index().url },
    ...(props.variety
        ? [{ title: props.variety.display_name ?? props.variety.name, href: farmer.vegetables.show(props.variety.id).url }]
        : []),
])

const chartData = computed(() => {
    if (!props.variety?.recent_prices?.length) return null

    const prices = [...props.variety.recent_prices].sort(
        (a, b) => new Date(a.recorded_at).getTime() - new Date(b.recorded_at).getTime()
    )

    return {
        labels: prices.map((p) => p.recorded_at),
        datasets: [
            {
                label: 'Max (₱/kg)',
                data: prices.map((p) => p.price_max),
                borderColor: 'rgb(99, 102, 241)',
                backgroundColor: 'rgba(99, 102, 241, 0.08)',
                fill: true,
                tension: 0.4,
                pointRadius: 3,
                pointHoverRadius: 5,
            },
            {
                label: 'Min (₱/kg)',
                data: prices.map((p) => p.price_min),
                borderColor: 'rgb(34, 197, 94)',
                backgroundColor: 'rgba(34, 197, 94, 0.08)',
                fill: true,
                tension: 0.4,
                pointRadius: 3,
                pointHoverRadius: 5,
            },
        ],
    }
})

const chartOptions: ChartOptions<'line'> = {
    responsive: true,
    maintainAspectRatio: true,
    interaction: { mode: 'index', intersect: false },
    plugins: {
        legend: {
            position: 'top',
            labels: { boxWidth: 12, padding: 16, font: { size: 12 } },
        },
        tooltip: {
            callbacks: { label: (ctx) => ` ₱${(ctx.raw as number).toFixed(2)}` },
        },
    },
    scales: {
        x: {
            grid: { display: false },
            ticks: { font: { size: 11 }, maxRotation: 45 },
        },
        y: {
            grid: { color: 'rgba(0,0,0,0.05)' },
            ticks: { font: { size: 11 }, callback: (value) => `₱${value}` },
        },
    },
}

const tableRows = computed(() =>
    props.variety ? [...props.variety.recent_prices].sort(
        (a, b) => new Date(b.recorded_at).getTime() - new Date(a.recorded_at).getTime()
    ) : []
)

const trendConfig = computed(() => {
    if (!props.variety) return null
    const lp = props.variety.latest_price
    const rp = props.variety.recent_prices
    if (!lp || rp.length < 2) return null
    const sorted = [...rp].sort((a, b) => new Date(b.recorded_at).getTime() - new Date(a.recorded_at).getTime())
    const latest = sorted[0].price_max
    const previous = sorted[1].price_max
    if (latest > previous) return { icon: TrendingUp, label: 'Trending up', class: 'text-red-500' }
    if (latest < previous) return { icon: TrendingDown, label: 'Trending down', class: 'text-green-600' }
    return { icon: Minus, label: 'Stable', class: 'text-muted-foreground' }
})

const freshnessConfig = {
    recent: { label: 'Recently Updated', class: 'bg-green-500/10 text-green-700 dark:text-green-400 border-green-500/20' },
    stable: { label: 'Stable', class: 'bg-blue-500/10 text-blue-700 dark:text-blue-400 border-blue-500/20' },
    'very stable': { label: 'Older Price', class: 'bg-amber-500/10 text-amber-700 dark:text-amber-400 border-amber-500/20' },
    stale: { label: 'Stale Price', class: 'bg-red-500/10 text-red-700 dark:text-red-400 border-red-500/20' },
} as const
</script>

<template>

    <Head :title="variety?.display_name ?? 'Vegetable Detail'" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 lg:p-6">

            <Deferred data="variety">
                <template #fallback>
                    <div class="flex flex-col gap-6">
                        <Skeleton class="h-8 w-64" />
                        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                            <Skeleton v-for="i in 4" :key="i" class="h-24 rounded-xl" />
                        </div>
                        <Skeleton class="h-72 w-full rounded-xl" />
                        <Skeleton class="h-64 w-full rounded-xl" />
                    </div>
                </template>

                <template v-if="variety">
                    <!-- Header -->
                    <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                        <Heading :title="variety.display_name ?? variety.name"
                            :description="variety.vegetable.category.name" />
                        <div class="flex items-center gap-2">
                            <Badge v-if="variety.latest_price" variant="outline"
                                :class="freshnessConfig[variety.latest_price.freshness]?.class">
                                {{ freshnessConfig[variety.latest_price.freshness]?.label }}
                            </Badge>
                            <span class="flex items-center gap-1 text-sm text-muted-foreground">
                                <Heart class="size-3.5" />
                                {{ variety.hearts_count }}
                            </span>
                        </div>
                    </div>

                    <!-- KPI row -->
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                        <!-- Min price -->
                        <div class="rounded-xl border bg-muted/30 p-4">
                            <p class="text-xs text-muted-foreground mb-1">Min Price</p>
                            <p class="font-mono text-xl font-bold text-green-600 dark:text-green-400">
                                {{ variety.latest_price ? `₱${variety.latest_price.price_min.toFixed(2)}` : '—' }}
                            </p>
                        </div>

                        <!-- Max price -->
                        <div class="rounded-xl border bg-muted/30 p-4">
                            <p class="text-xs text-muted-foreground mb-1">Max Price</p>
                            <p class="font-mono text-xl font-bold text-indigo-600 dark:text-indigo-400">
                                {{ variety.latest_price ? `₱${variety.latest_price.price_max.toFixed(2)}` : '—' }}
                            </p>
                        </div>

                        <!-- Active supplies -->
                        <div class="rounded-xl border bg-muted/30 p-4">
                            <p class="text-xs text-muted-foreground mb-1">Active Supplies</p>
                            <div class="flex items-baseline gap-2">
                                <p class="text-xl font-bold tabular-nums">{{ variety.supply_count }}</p>
                                <Package class="size-4 text-muted-foreground" />
                            </div>
                        </div>

                        <!-- Active demands -->
                        <div class="rounded-xl border bg-muted/30 p-4">
                            <p class="text-xs text-muted-foreground mb-1">Active Demands</p>
                            <div class="flex items-baseline gap-2">
                                <p class="text-xl font-bold tabular-nums">{{ variety.demand_count }}</p>
                                <component v-if="trendConfig" :is="trendConfig.icon" class="size-4"
                                    :class="trendConfig.class" />
                            </div>
                        </div>
                    </div>

                    <!-- Price trend chart -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-sm font-semibold">Price History</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div v-if="chartData" class="rounded-lg border p-3">
                                <Line :data="chartData" :options="chartOptions" />
                            </div>
                            <div v-else
                                class="flex items-center gap-2 rounded-lg border border-dashed p-6 text-sm text-muted-foreground">
                                <AlertCircle class="size-4 shrink-0" />
                                No price history available for this variety.
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Price history table -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-sm font-semibold">
                                Price Records
                                <span class="ml-1.5 font-normal text-muted-foreground">(last {{ tableRows.length
                                }})</span>
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div v-if="tableRows.length" class="overflow-hidden rounded-lg border">
                                <table class="w-full text-sm">
                                    <thead class="bg-muted/50">
                                        <tr>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-muted-foreground">
                                                Date</th>
                                            <th class="px-3 py-2 text-right text-xs font-medium text-muted-foreground">
                                                Min (₱/kg)</th>
                                            <th class="px-3 py-2 text-right text-xs font-medium text-muted-foreground">
                                                Max (₱/kg)</th>
                                            <th class="px-3 py-2 text-right text-xs font-medium text-muted-foreground">
                                                Avg (₱/kg)</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y">
                                        <tr v-for="(entry, index) in tableRows" :key="index"
                                            class="transition-colors hover:bg-muted/30"
                                            :class="{ 'bg-primary/5': index === 0 }">
                                            <td class="px-3 py-2 text-xs text-muted-foreground">
                                                {{ entry.recorded_at }}
                                                <span v-if="index === 0"
                                                    class="ml-1.5 rounded bg-primary/10 px-1 py-0.5 text-[10px] font-medium text-primary">latest</span>
                                            </td>
                                            <td
                                                class="px-3 py-2 text-right font-mono text-xs text-green-700 dark:text-green-400">
                                                {{ entry.price_min.toFixed(2) }}
                                            </td>
                                            <td
                                                class="px-3 py-2 text-right font-mono text-xs text-indigo-700 dark:text-indigo-400">
                                                {{ entry.price_max.toFixed(2) }}
                                            </td>
                                            <td class="px-3 py-2 text-right font-mono text-xs text-foreground">
                                                {{ ((entry.price_min + entry.price_max) / 2).toFixed(2) }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div v-else
                                class="flex items-center gap-2 rounded-lg border border-dashed p-6 text-sm text-muted-foreground">
                                <AlertCircle class="size-4 shrink-0" />
                                No price records found.
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Supply by municipality -->
                    <Card v-if="variety.supply_municipalities.length">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2 text-sm font-semibold">
                                <MapPin class="size-4 text-primary" />
                                Supply by Municipality
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                <div v-for="entry in variety.supply_municipalities" :key="entry.name"
                                    class="flex items-center justify-between rounded-lg border bg-muted/30 px-4 py-3">
                                    <span class="truncate text-sm font-medium">{{ entry.name }}</span>
                                    <Badge variant="secondary" class="ml-2 shrink-0 font-mono text-xs">
                                        {{ entry.total_kg.toLocaleString() }} kg
                                    </Badge>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                </template>
            </Deferred>

        </div>
    </AppLayout>
</template>
