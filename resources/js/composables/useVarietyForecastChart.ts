import {
    CategoryScale,
    Chart as ChartJS,
    type ChartOptions,
    Legend,
    LinearScale,
    LineElement,
    PointElement,
    Tooltip,
} from 'chart.js'
import { computed, type MaybeRefOrGetter, toValue } from 'vue'
import type { ForecastPoint, MonthlyActivity } from '@/types/resources/product'

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Tooltip, Legend)

/**
 * Produces a line chart that bridges the last 6 months of actuals (solid lines)
 * into the 6-month forecast (dashed lines) with a shared anchor point so the
 * transition reads as continuous.
 */
export function useVarietyForecastChart(
    monthlyActivity: MaybeRefOrGetter<MonthlyActivity[] | null | undefined>,
    forecast: MaybeRefOrGetter<ForecastPoint[] | null | undefined>,
) {
    const chartData = computed(() => {
        const history = toValue(monthlyActivity)
        const fc = toValue(forecast)

        if (!history?.length && !fc?.length) return null

        // Last 6 months of history for context; full 6-month forecast
        const historical = history?.slice(-6) ?? []
        const projected = fc ?? []

        if (!historical.length && !projected.length) return null

        const histLabels = historical.map((m) => m.label)
        const fcLabels = projected.map((m) => m.label)
        const allLabels = [...histLabels, ...fcLabels]

        const histLen = histLabels.length
        const fcLen = fcLabels.length

        // Totals from the fulfillment + expired breakdown
        const totalSupply = (m: MonthlyActivity) =>
            m.supply_fulfilled_kg + m.supply_expired_kg
        const totalDemand = (m: MonthlyActivity) =>
            m.demand_fulfilled_kg + m.demand_expired_kg

        // Anchor values — the last historical point is shared by both datasets
        // so the dashed line visually continues from the solid line.
        const anchorSupply = historical.length ? totalSupply(historical.at(-1)!) : null
        const anchorDemand = historical.length ? totalDemand(historical.at(-1)!) : null

        // Historical datasets: data at [0..histLen-1], nulls after
        const histSupplyData = [
            ...historical.map(totalSupply),
            ...Array<null>(fcLen).fill(null),
        ]
        const histDemandData = [
            ...historical.map(totalDemand),
            ...Array<null>(fcLen).fill(null),
        ]

        // Forecast datasets: nulls until the anchor position (histLen-1),
        // then anchor value + forecast values
        const fcSupplyData = [
            ...Array<null>(histLen - 1).fill(null),
            anchorSupply,
            ...projected.map((m) => m.supply_kg),
        ]
        const fcDemandData = [
            ...Array<null>(histLen - 1).fill(null),
            anchorDemand,
            ...projected.map((m) => m.demand_kg),
        ]

        return {
            labels: allLabels,
            datasets: [
                {
                    label: 'Supply (actual)',
                    data: histSupplyData,
                    borderColor: 'rgb(34, 197, 94)',
                    backgroundColor: 'rgba(34, 197, 94, 0.08)',
                    borderWidth: 2,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    tension: 0.3,
                    spanGaps: false,
                },
                {
                    label: 'Demand (actual)',
                    data: histDemandData,
                    borderColor: 'rgb(249, 115, 22)',
                    backgroundColor: 'rgba(249, 115, 22, 0.08)',
                    borderWidth: 2,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    tension: 0.3,
                    spanGaps: false,
                },
                {
                    label: 'Supply (forecast)',
                    data: fcSupplyData,
                    borderColor: 'rgb(34, 197, 94)',
                    backgroundColor: 'rgba(34, 197, 94, 0.0)',
                    borderWidth: 2,
                    borderDash: [6, 4],
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    pointStyle: 'circle' as const,
                    tension: 0.3,
                    spanGaps: false,
                },
                {
                    label: 'Demand (forecast)',
                    data: fcDemandData,
                    borderColor: 'rgb(249, 115, 22)',
                    backgroundColor: 'rgba(249, 115, 22, 0.0)',
                    borderWidth: 2,
                    borderDash: [6, 4],
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    tension: 0.3,
                    spanGaps: false,
                },
            ],
        }
    })

    // histLen is reactive; capture it for the annotation plugin below
    const histLen = computed(() => {
        const history = toValue(monthlyActivity)
        return Math.max(0, (history?.slice(-6).length ?? 0) - 1)
    })

    /**
     * Inline Chart.js plugin that draws a vertical dashed separator between
     * the last historical data point and the first forecast point.
     * No external plugin package required.
     */
    const forecastDividerPlugin = {
        id: 'forecastDivider',
        afterDraw(chart: ChartJS) {
            const idx = histLen.value
            if (idx <= 0) return

            const { ctx, chartArea, scales } = chart as ChartJS & {
                scales: { x: { getPixelForValue: (v: number) => number } }
            }
            const xPos = scales.x.getPixelForValue(idx)

            ctx.save()
            ctx.strokeStyle = 'rgba(100,116,139,0.35)'
            ctx.lineWidth = 1
            ctx.setLineDash([4, 4])
            ctx.beginPath()
            ctx.moveTo(xPos, chartArea.top)
            ctx.lineTo(xPos, chartArea.bottom)
            ctx.stroke()

            ctx.font = '10px system-ui, sans-serif'
            ctx.fillStyle = 'rgba(100,116,139,0.7)'
            ctx.textAlign = 'left'
            ctx.fillText('▸ forecast', xPos + 5, chartArea.top + 12)
            ctx.restore()
        },
    }

    const chartOptions: ChartOptions<'line'> = {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    boxWidth: 8,
                    boxHeight: 2,
                    padding: 12,
                    font: { size: 10 },
                    usePointStyle: false,
                },
            },
            tooltip: {
                callbacks: {
                    label: (ctx) => {
                        const raw = ctx.raw as number | null
                        if (raw === null) return ''
                        return ` ${ctx.dataset.label}: ${raw.toLocaleString('en-PH', { minimumFractionDigits: 0, maximumFractionDigits: 2 })} kg`
                    },
                },
            },
        },
        scales: {
            x: {
                grid: { display: false },
                ticks: { font: { size: 10 }, maxRotation: 45, maxTicksLimit: 12 },
            },
            y: {
                beginAtZero: true,
                grid: { color: 'rgba(0,0,0,0.04)' },
                ticks: {
                    font: { size: 10 },
                    callback: (value) => `${Number(value).toLocaleString()} kg`,
                },
            },
        },
    }

    return { chartData, chartOptions, forecastDividerPlugin }
}