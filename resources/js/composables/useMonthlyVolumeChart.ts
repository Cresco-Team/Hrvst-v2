import {
    BarElement,
    CategoryScale,
    Chart as ChartJS,
    type ChartOptions,
    Legend,
    LinearScale,
    LineElement,
    PointElement,
    Title,
    Tooltip,
} from 'chart.js'
import { computed, type MaybeRefOrGetter, toValue } from 'vue'
import type { ForecastPoint, MonthlyActivity } from '@/types/resources/product'

ChartJS.register(
    BarElement,
    CategoryScale,
    LinearScale,
    LineElement,
    PointElement,
    Title,
    Tooltip,
    Legend,
)

export function useMonthlyVolumeChart(
    activity: MaybeRefOrGetter<MonthlyActivity[] | null | undefined>,
    forecast?: MaybeRefOrGetter<ForecastPoint[] | null | undefined>,
) {
    const chartData = computed(() => {
        const allMonths = toValue(activity)
        const fc = forecast ? (toValue(forecast) ?? []) : []

        if (!allMonths?.length) return null

        // Last 6 months of actuals for display — forecast covers the next 6
        const historical = allMonths.slice(-6)
        const histLen = historical.length
        const fcLen = fc.length

        const allLabels = [
            ...historical.map((m) => m.label),
            ...fc.map((m) => m.label),
        ]

        // Actual bar datasets: values at [0..histLen-1], null in forecast positions
        const barData = (values: number[]) => [
            ...values,
            ...Array<null>(fcLen).fill(null),
        ]

        // Bridge point: last actual totals shared by bar and line so the
        // dashed forecast line visually grows out of the last bar.
        const lastActual = historical.at(-1)
        const anchorSupply = lastActual
            ? lastActual.supply_fulfilled_kg + lastActual.supply_expired_kg
            : null
        const anchorDemand = lastActual
            ? lastActual.demand_fulfilled_kg + lastActual.demand_expired_kg
            : null

        // Forecast line datasets: nulls → anchor at histLen-1 → forecast values
        const lineData = (anchor: number | null, values: number[]) => [
            ...Array<null>(histLen - 1).fill(null),
            anchor,
            ...values,
        ]

        // eslint-disable-next-line @typescript-eslint/no-explicit-any
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
                order: 2,
            },
            {
                type: 'bar',
                label: 'Supply — Expired',
                data: barData(historical.map((m) => m.supply_expired_kg)),
                backgroundColor: 'rgba(34, 197, 94, 0.35)',
                borderColor: 'rgb(34, 197, 94)',
                borderWidth: 1,
                borderRadius: 4,
                stack: 'supply',
                order: 2,
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
                order: 2,
            },
            {
                type: 'bar',
                label: 'Demand — Expired',
                data: barData(historical.map((m) => m.demand_expired_kg)),
                backgroundColor: 'rgba(249, 115, 22, 0.35)',
                borderColor: 'rgb(249, 115, 22)',
                borderWidth: 1,
                borderRadius: 4,
                stack: 'demand',
                order: 2,
            },
        ]

        // ── Forecast lines (only when data exists) ────────────────────────────
        if (fcLen > 0) {
            datasets.push(
                {
                    type: 'line',
                    label: 'Supply (forecast)',
                    data: lineData(anchorSupply, fc.map((m) => m.supply_kg)),
                    borderColor: 'rgb(34, 197, 94)',
                    backgroundColor: 'transparent',
                    borderWidth: 2,
                    borderDash: [6, 4],
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    tension: 0.3,
                    spanGaps: false,
                    order: 1,
                },
                {
                    type: 'line',
                    label: 'Demand (forecast)',
                    data: lineData(anchorDemand, fc.map((m) => m.demand_kg)),
                    borderColor: 'rgb(249, 115, 22)',
                    backgroundColor: 'transparent',
                    borderWidth: 2,
                    borderDash: [6, 4],
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    tension: 0.3,
                    spanGaps: false,
                    order: 1,
                },
            )
        }

        return { labels: allLabels, datasets }
    })

    /**
     * Inline plugin — draws a vertical dashed divider between the last actual
     * bar and the first forecast point, plus a "▸ forecast" label.
     * No external annotation plugin required.
     */
    const forecastDividerPlugin = {
        id: 'forecastDivider',
        afterDraw(chart: ChartJS) {
            const fc = forecast ? (toValue(forecast) ?? []) : []
            if (!fc.length) return

            const months = toValue(activity)
            const histLen = months?.slice(-6).length ?? 0
            // Divider sits between the last bar (histLen-1) and first forecast
            const dividerIdx = histLen - 1
            if (dividerIdx <= 0) return

            // eslint-disable-next-line @typescript-eslint/no-explicit-any
            const { ctx, chartArea, scales } = chart as any
            const xPos: number = scales.x.getPixelForValue(dividerIdx)

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
                        return ` ${ctx.dataset.label}: ${(raw).toLocaleString('en-PH', { minimumFractionDigits: 0, maximumFractionDigits: 2 })} kg`
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
