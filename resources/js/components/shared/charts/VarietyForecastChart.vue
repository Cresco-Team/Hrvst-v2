<script setup lang="ts">
import { AlertCircle } from 'lucide-vue-next'
import { Line } from 'vue-chartjs'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import type { ForecastPoint, MonthlyActivity } from '@/types/resources/product'
import { useVarietyForecastChart } from '@/composables/useVarietyForecastChart';

const props = defineProps<{
    monthlyActivity: MonthlyActivity[]
    forecast: ForecastPoint[]
}>()

const { chartData, chartOptions, forecastDividerPlugin } = useVarietyForecastChart(
    () => props.monthlyActivity,
    () => props.forecast,
)
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle class="text-sm font-semibold">
                6-Month Supply &amp; Demand Forecast
            </CardTitle>
            <CardDescription class="text-xs text-muted-foreground">
                Seasonally adjusted projection from 3-year historical patterns.
                Dashed lines indicate forecast values.
            </CardDescription>
        </CardHeader>
        <CardContent>
            <div v-if="chartData" class="relative h-52 w-full sm:h-64">
                <Line
                    :data="chartData"
                    :options="chartOptions"
                    :plugins="[forecastDividerPlugin]"
                />
            </div>
            <div
                v-else
                class="flex items-center gap-2 rounded-lg border border-dashed p-6 text-sm text-muted-foreground"
            >
                <AlertCircle class="size-4 shrink-0" />
                Insufficient historical data to generate a forecast.
            </div>
        </CardContent>
    </Card>
</template>
