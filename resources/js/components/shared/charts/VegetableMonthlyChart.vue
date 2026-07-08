<script setup lang="ts">
import { AlertCircle, Info } from 'lucide-vue-next'
import { Bar } from 'vue-chartjs'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip'
import { useMonthlyVolumeChart } from '@/composables/useMonthlyVolumeChart'
import type { ForecastPoint, MonthlyActivity } from '@/types/resources/product'

const props = defineProps<{
    monthlyActivity: MonthlyActivity[]
    forecast?: ForecastPoint[]
    monthsOfHistory?: number
    forecastConfidence?: string
}>()

const { chartData, chartOptions, forecastDividerPlugin } = useMonthlyVolumeChart(
    () => props.monthlyActivity,
    () => props.forecast,
)

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
    <Card>
        <CardHeader class="flex flex-row items-center justify-between gap-2 space-y-0">
            <CardTitle class="text-sm font-semibold">
                Market Volume{{ hasForecast() ? ' & 6-Month Forecast' : '' }}
            </CardTitle>

            <TooltipProvider v-if="hasForecast() && forecastConfidence" :delay-duration="200">
                <Tooltip>
                    <TooltipTrigger as-child>
                        <Badge variant="outline" class="cursor-help gap-1 text-xs font-normal">
                            <Info class="size-3" />
                            {{ confidenceLabel[forecastConfidence] ?? 'Forecast' }}
                        </Badge>
                    </TooltipTrigger>
                    <TooltipContent>
                        <p class="max-w-[220px] text-xs">
                            {{ confidenceTooltip[forecastConfidence] }}
                        </p>
                    </TooltipContent>
                </Tooltip>
            </TooltipProvider>
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

            <div
                v-if="chartData && !hasForecast() && (monthsOfHistory ?? 0) < MIN_MONTHS_FOR_FORECAST"
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
