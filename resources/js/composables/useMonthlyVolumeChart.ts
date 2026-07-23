import type { ScriptableContext } from 'chart.js'
import {
    BarController,
    BarElement,
    CategoryScale,
    Chart as ChartJS,
    type ChartOptions,
    Filler,
    Legend,
    LinearScale,
    LineController,
    LineElement,
    PointElement,
    Title,
    Tooltip,
} from 'chart.js'
import { computed, type MaybeRefOrGetter, toValue } from 'vue'
import type { ForecastPoint, MonthlyActivity } from '@/types/resources/product'

ChartJS.register(
    BarController,
    BarElement,
    LineController,
    LineElement,
    PointElement,
    Filler,
    CategoryScale,
    LinearScale,
    Title,
    Tooltip,
    Legend,
)

export type VolumeChartType = 'bar' | 'line'

type MetricKey =
    | 'supply_fulfilled_kg'
    | 'supply_expired_kg'
    | 'demand_fulfilled_kg'
    | 'demand_expired_kg'

interface SeriesConfig {
    key: MetricKey
    label: string
    rgb: string
    stack: 'supply' | 'demand'
    historicalAlpha: number
    forecastAlpha: number
    radius: number
}

const SERIES: SeriesConfig[] = [
    {
        key: 'supply_fulfilled_kg',
        label: 'Fulfilled Suply',
        rgb: '34, 197, 94',
        stack: 'supply',
        historicalAlpha: 0.9,
        forecastAlpha: 0.35,
        radius: 4,
    },
    {
        key: 'supply_expired_kg',
        label: 'Expired Supply',
        rgb: '166, 166, 166',
        stack: 'supply',
        historicalAlpha: 0.3,
        forecastAlpha: 0.15,
        radius: 0,
    },
    {
        key: 'demand_fulfilled_kg',
        label: 'Fulfilled Demand',
        rgb: '249, 115, 22',
        stack: 'demand',
        historicalAlpha: 0.9,
        forecastAlpha: 0.35,
        radius: 4,
    },
    {
        key: 'demand_expired_kg',
        label: 'Expired Demand',
        rgb: '166, 166, 166',
        stack: 'demand',
        historicalAlpha: 0.3,
        forecastAlpha: 0.15,
        radius: 0,
    },
]

function formatKgAxis(value: number): string {
    if (Math.abs(value) >= 1000) {
        const scaled = value / 1000
        return `${scaled.toLocaleString('en-PH', {
            minimumFractionDigits: 0,
            maximumFractionDigits: scaled % 1 === 0 ? 0 : 1,
        })}k kg`
    }
    return `${value} kg`
}

export function useMonthlyVolumeChart(
    activity: MaybeRefOrGetter<MonthlyActivity[] | null | undefined>,
    forecast?: MaybeRefOrGetter<ForecastPoint[] | null | undefined>,
    chartType: MaybeRefOrGetter<VolumeChartType> = 'bar',
) {
    const chartData = computed(() => {
        const allMonths = toValue(activity)
        const fc = forecast ? (toValue(forecast) ?? []) : []
        const type = toValue(chartType)

        if (!allMonths?.length) return null

        const historical = allMonths.slice(-6)
        const histLen = historical.length

        const allLabels = [
            ...historical.map((m) => m.label),
            ...fc.map((m) => m.label),
        ]

        const isForecastIndex = (dataIndex: number) => dataIndex >= histLen

        const datasets = SERIES.map((series) => {
            const historicalValues = historical.map(
                (m) => (m as any)[series.key] as number,
            )
            const forecastValues = fc.map((m) => m[series.key])
            const data = [...historicalValues, ...forecastValues]

            if (type === 'line') {
                return {
                    type: 'line' as const,
                    label: series.label,
                    data,
                    borderColor: (ctx: ScriptableContext<'bar'>) =>
                        `rgba(${series.rgb}, ${isForecastIndex(ctx.dataIndex ?? 0) ? 0.5 : 1})`,
                    backgroundColor: `rgba(${series.rgb}, 0.08)`,
                    pointBackgroundColor: `rgb(${series.rgb})`,
                    pointRadius: (ctx: ScriptableContext<'bar'>) =>
                        isForecastIndex(ctx.dataIndex ?? 0) ? 2 : 3,
                    borderWidth: 2,
                    borderDash: (ctx: ScriptableContext<'bar'>) =>
                        isForecastIndex(ctx.dataIndex ?? 0) ? [4, 3] : [],
                    tension: 0.3,
                    fill: false,
                }
            }

            return {
                type: 'bar' as const,
                label: series.label,
                data,
                backgroundColor: (ctx: ScriptableContext<'bar'>) =>
                    `rgba(${series.rgb}, ${isForecastIndex(ctx.dataIndex) ? series.forecastAlpha : series.historicalAlpha})`,
                borderColor: (ctx: ScriptableContext<'bar'>) =>
                    `rgba(${series.rgb}, ${isForecastIndex(ctx.dataIndex) ? 0.7 : 1})`,
                borderWidth: 1,
                borderDash: (ctx: ScriptableContext<'bar'>) =>
                    isForecastIndex(ctx.dataIndex) ? [4, 3] : [],
                borderRadius: series.radius,
                stack: series.stack,
            }
        })

        return { labels: allLabels, datasets }
    })

    const forecastDividerPlugin = {
        id: 'forecastDivider',
        afterDraw(chart: ChartJS) {
            const fc = forecast ? (toValue(forecast) ?? []) : []
            if (!fc.length) return

            const months = toValue(activity)
            const histLen = months?.slice(-6).length ?? 0
            if (histLen <= 0) return

            const { ctx, chartArea, scales } = chart as any

            const xLeft: number = scales.x.getPixelForValue(histLen - 1)
            const xRight: number = scales.x.getPixelForValue(histLen)
            const xPos = (xLeft + xRight) / 2

            ctx.save()
            ctx.strokeStyle = 'rgba(100,116,139,0.3)'
            ctx.lineWidth = 1
            ctx.setLineDash([4, 3])
            ctx.beginPath()
            ctx.moveTo(xPos, chartArea.top)
            ctx.lineTo(xPos, chartArea.bottom)
            ctx.stroke()

            ctx.setLineDash([])
            ctx.font = '10px system-ui, sans-serif'
            ctx.fillStyle = 'rgba(100,116,139,0.65)'
            ctx.textAlign = 'left'
            ctx.fillText('▸ forecast', xPos + 5, chartArea.top + 12)
            ctx.restore()
        },
    }

    const chartOptions = computed<ChartOptions<any>>(() => {
        const type = toValue(chartType)
        const stacked = type === 'bar'

        return {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        boxWidth: 8,
                        boxHeight: 8,
                        padding: 8,
                        font: { size: 10 },
                    },
                },
                tooltip: {
                    callbacks: {
                        label: (ctx: ScriptableContext<'bar'>) => {
                            const raw = ctx.raw as number | null
                            if (raw === null || raw === undefined) return ''
                            return ` ${ctx.dataset.label}: ${raw.toLocaleString('en-PH', { minimumFractionDigits: 0, maximumFractionDigits: 2 })} kg`
                        },
                    },
                },
            },
            scales: {
                x: {
                    stacked,
                    grid: { display: false },
                    ticks: {
                        font: { size: 11 },
                        maxRotation: 45,
                        maxTicksLimit: 12,
                    },
                },
                y: {
                    stacked,
                    beginAtZero: true,
                    grid: { color: 'rgba(0,0,0,0.05)' },
                    ticks: {
                        font: { size: 11 },
                        callback: (value: number) =>
                            formatKgAxis(Number(value)),
                    },
                },
            },
        }
    })

    return { chartData, chartOptions, forecastDividerPlugin }
}
