<script setup lang="ts">
import { AlertCircle } from 'lucide-vue-next'
import { Line } from 'vue-chartjs'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { usePriceHistoryChart } from '@/composables/usePriceHistoryChart'
import type { PriceHistoryResource } from '@/types/resources/product'

const props = defineProps<{
    recentPrices: PriceHistoryResource[]
}>()

const { chartData, chartOptions } = usePriceHistoryChart(() => props.recentPrices)
</script>

<template>
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
</template>
