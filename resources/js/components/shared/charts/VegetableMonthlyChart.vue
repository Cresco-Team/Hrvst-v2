<script setup lang="ts">
import { AlertCircle } from 'lucide-vue-next'
import { Bar } from 'vue-chartjs'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { useMonthlyVolumeChart } from '@/composables/useMonthlyVolumeChart'
import type { ForecastPoint, MonthlyActivity } from '@/types/resources/product'

const props = defineProps<{
    monthlyActivity: MonthlyActivity[]
    forecast?: ForecastPoint[]
}>()

const { chartData, chartOptions, forecastDividerPlugin } = useMonthlyVolumeChart(
    () => props.monthlyActivity,
    () => props.forecast,
)
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle class="text-sm font-semibold">
                Market Volume &amp; 6-Month Forecast
            </CardTitle>
        </CardHeader>
        <CardContent>
            <div v-if="chartData" class="relative h-48 w-full sm:h-64">
                <Bar
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
                No completed market activity recorded for this vegetable.
            </div>
        </CardContent>
    </Card>
</template>
