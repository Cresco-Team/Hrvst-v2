import {
    BarElement,
    CategoryScale,
    Chart as ChartJS,
    type ChartOptions,
    Legend,
    LinearScale,
    Title,
    Tooltip,
} from 'chart.js'
import { computed, type MaybeRefOrGetter, toValue } from 'vue'
import type { ForecastPoint, MonthlyActivity } from '@/types/resources/product'

ChartJS.register(BarElement, CategoryScale, LinearScale, Title, Tooltip, Legend)

export function useMonthlyVolumeChart(
    activity: MaybeRefOrGetter<MonthlyActivity[] | null | undefined>,
    forecast?: MaybeRefOrGetter<ForecastPoint[] | null | undefined>,
) {
    const chartData = computed(() => {
        const allMonths = toValue(activity)
        const fc = forecast ? (toValue(forecast) ?? []) : []

        if (!allMonths?.length) return null

        const historical = allMonths.slice(-6)
        const histLen = historical.length
        const fcLen = fc.length

        const allLabels = [
            ...historical.map((m) => m.label),
            ...fc.map((m) => m.label),
        ]

        const barData = (values: number[]) => [
            ...values,
            ...Array<null>(fcLen).fill(null),
        ]
        const forecastBarData = (values: number[]) => [
            ...Array<null>(histLen).fill(null),
            ...values,
        ]

        const datasets: any[] = [
            // ── Actual stacked bars ───────────────────────────────────────────
            {
                type: 'bar',
                label: 'Supply — Fulfilled',
                data: barData(historical.map((m) => m.supply_fulfilled_kg)),
                backgroundColor: 'rgba(34, 197, 94, 0.9)',
                borderColor: 'rgb(34, 197, 94)',
                borderWidth: 1,
                borderRadius: 4,
                stack: 'supply',
            },
            {
                type: 'bar',
                label: 'Supply — Expired',
                data: barData(historical.map((m) => m.supply_expired_kg)),
                backgroundColor: 'rgba(166, 166, 166, 0.3)',
                borderColor: 'rgb(166, 166, 166)',
                borderWidth: 1,
                stack: 'supply',
            },
            {
                type: 'bar',
                label: 'Demand — Fulfilled',
                data: barData(historical.map((m) => m.demand_fulfilled_kg)),
                backgroundColor: 'rgba(249, 115, 22, 0.9)',
                borderColor: 'rgb(249, 115, 22)',
                borderWidth: 1,
                borderRadius: 4,
                stack: 'demand',
            },
            {
                type: 'bar',
                label: 'Demand — Expired',
                data: barData(historical.map((m) => m.demand_expired_kg)),
                backgroundColor: 'rgba(166, 166, 166, 0.3)',
                borderColor: 'rgb(166, 166, 166)',
                borderWidth: 1,
                stack: 'demand',
            },
        ]

        if (fcLen > 0) {
            datasets.push(
                {
                    type: 'bar',
                    label: 'Supply (forecast)',
                    data: forecastBarData(fc.map((m) => m.supply_kg)),
                    backgroundColor: 'rgba(34, 197, 94, 0.25)',
                    borderColor: 'rgba(34, 197, 94, 0.6)',
                    borderWidth: 1,
                    borderRadius: 4,
                    stack: 'supply',
                },
                {
                    type: 'bar',
                    label: 'Demand (forecast)',
                    data: forecastBarData(fc.map((m) => m.demand_kg)),
                    backgroundColor: 'rgba(249, 115, 22, 0.25)',
                    borderColor: 'rgba(249, 115, 22, 0.6)',
                    borderWidth: 1,
                    borderRadius: 4,
                    stack: 'demand',
                },
            )
        }

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

    const chartOptions: ChartOptions<'bar'> = {
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
                    label: (ctx) => {
                        const raw = ctx.raw as number | null
                        if (raw === null || raw === undefined) return ''
                        return ` ${ctx.dataset.label}: ${raw.toLocaleString('en-PH', { minimumFractionDigits: 0, maximumFractionDigits: 2 })} kg`
                    },
                },
            },
        },
        scales: {
            x: {
                stacked: true,
                grid: { display: false },
                ticks: {
                    font: { size: 11 },
                    maxRotation: 45,
                    maxTicksLimit: 12,
                },
            },
            y: {
                stacked: true,
                beginAtZero: true,
                grid: { color: 'rgba(0,0,0,0.05)' },
                ticks: {
                    font: { size: 11 },
                    callback: (value) => `${value} kg`,
                },
            },
        },
    }

    return { chartData, chartOptions, forecastDividerPlugin }
}
