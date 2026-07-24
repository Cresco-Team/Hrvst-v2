<script setup lang="ts">
import { AlertCircle, ChartColumn, ChartLine, Info } from 'lucide-vue-next'
import { ref } from 'vue'
import { Bar, Line } from 'vue-chartjs'
import EmptyState from '@/components/EmptyState.vue'
import AppTooltip from '@/components/templates/AppTooltip.vue'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group'
import { useMonthlyBarChart } from '@/composables/useMonthlyBarChart'
import { useMonthlyLineChart } from '@/composables/useMonthlyLineChart'
import type { ForecastPoint, MonthlyActivity } from '@/types/resources/product'

const props = defineProps<{
    monthlyActivity: MonthlyActivity[]
    forecast?: ForecastPoint[]
    monthsOfHistory?: number
    forecastConfidence?: string
}>()

const chartType = ref<'bar' | 'line'>('bar')

const {
    chartData: barChartData,
    chartOptions: barChartOptions,
    forecastDividerPlugin: barForecastDividerPlugin,
} = useMonthlyBarChart(() => props.monthlyActivity, () => props.forecast)

const {
    chartData: lineChartData,
    chartOptions: lineChartOptions,
    forecastDividerPlugin: lineForecastDividerPlugin,
} = useMonthlyLineChart(() => props.monthlyActivity, () => props.forecast)

const MIN_MONTHS_FOR_FORECAST = 12

const hasForecast = () => (props.forecast?.length ?? 0) > 0

const confidenceLabel: Record<string, string> = {
    developing: 'Developing forecast',
    established: 'Established forecast',
    strong: 'Strong forecast',
}

const confidenceTooltip: Record<string, string> = {
    developing: 'Based on 12–35 months of history — seasonal pattern is emerging but not yet fully confirmed.',
    established: 'Based on 36–59 months of history — seasonal pattern is well supported.',
    strong: 'Based on 60+ months of history — high-confidence seasonal projection.',
}
</script>

<template>
    <Card class="rounded-none border-0 shadow-none sm:rounded sm:border sm:shadow-sm">
        <CardHeader class="flex flex-row items-center justify-between gap-2 space-y-0 px-3 py-3 sm:px-6 sm:py-6">
            <CardTitle>
                {{ hasForecast() ? ' 6-Month Forecast' : 'Market Volume' }}
            </CardTitle>

            <div class="flex items-center gap-2">
                <AppTooltip
                    v-if="hasForecast() && forecastConfidence"
                    :content="confidenceTooltip[forecastConfidence]"
                >
                    <Badge
                        variant="outline"
                        class="cursor-help gap-1 text-xs font-normal"
                    >
                        <Info class="size-3" />
                        {{ confidenceLabel[forecastConfidence] ?? 'Forecast' }}
                    </Badge>
                </AppTooltip>

                <ToggleGroup
                    v-if="barChartData || lineChartData"
                    v-model="chartType"
                    type="single"
                    variant="outline"
                    size="sm"
                >
                    <ToggleGroupItem
                        value="bar"
                        aria-label="Bar chart"
                    >
                        <ChartColumn class="size-3.5" />
                    </ToggleGroupItem>
                    <ToggleGroupItem
                        value="line"
                        aria-label="Line chart"
                    >
                        <ChartLine class="size-3.5" />
                    </ToggleGroupItem>
                </ToggleGroup>
            </div>
        </CardHeader>
        <CardContent class="px-0 sm:px-6">
            <div
                v-if="barChartData || lineChartData"
                class="relative h-48 w-full sm:h-64"
            >
                <Bar
                    v-if="chartType === 'bar' && barChartData"
                    :data="barChartData"
                    :options="barChartOptions"
                    :plugins="[barForecastDividerPlugin]"
                />
                <Line
                    v-else-if="lineChartData"
                    :data="lineChartData"
                    :options="lineChartOptions"
                    :plugins="[lineForecastDividerPlugin]"
                />
            </div>

            <EmptyState
                v-else
                title="No completed activity record"
                :icon="AlertCircle"
            />

            <div
                v-if="(barChartData || lineChartData) && !hasForecast() && (monthsOfHistory ?? 0) < MIN_MONTHS_FOR_FORECAST"
                class="mt-3 flex items-center gap-2 rounded-lg border border-dashed bg-muted/30 p-3 text-xs text-muted-foreground"
            >
                <Info class="size-3.5 shrink-0" />
                <span>
                    Forecast unavailable — {{ monthsOfHistory ?? 0 }}/{{ MIN_MONTHS_FOR_FORECAST }} months
                    of data collected. A full year of history is needed to detect seasonal patterns.
                </span>
            </div>
        </CardContent>
    </Card>
</template>