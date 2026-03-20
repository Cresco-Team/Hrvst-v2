import { 
    BarElement, CategoryScale, Chart as ChartJS, type ChartOptions, Legend, LinearScale, Title, Tooltip,
} from 'chart.js'
import { computed, type MaybeRefOrGetter, toValue } from 'vue'
import type { MonthlyActivity } from '@/types/shared/vegetables'

ChartJS.register(BarElement, CategoryScale, LinearScale, Title, Tooltip, Legend)

export function useMonthlyVolumeChart(activity: MaybeRefOrGetter<MonthlyActivity[] | null | undefined>) {
    const chartData = computed(() => {
        const months = toValue(activity)

        if (!months?.length) return null

        return {
            labels: months.map((m) => m.label),
            datasets: [
                {
                    label: 'Supply — Archived',
                    data: months.map((m) => m.supply_archived_kg),
                    backgroundColor: 'rgba(34, 197, 94, 0.5)',
                    borderColor: 'rgb(34, 197, 94)',
                    borderWidth: 1,
                    borderRadius: 4,
                    stack: 'supply',
                },
                {
                    label: 'Supply — Fulfilled',
                    data: months.map((m) => m.supply_fulfilled_kg),
                    backgroundColor: 'rgba(34, 197, 94, 0.9)',
                    borderColor: 'rgb(34, 197, 94)',
                    borderWidth: 1,
                    borderRadius: 4,
                    stack: 'supply',
                },
                {
                    label: 'Demand — Archived',
                    data: months.map((m) => m.demand_archived_kg),
                    backgroundColor: 'rgba(99, 102, 241, 0.5)',
                    borderColor: 'rgb(99, 102, 241)',
                    borderWidth: 1,
                    borderRadius: 4,
                    stack: 'demand',
                },
                {
                    label: 'Demand — Fulfilled',
                    data: months.map((m) => m.demand_fulfilled_kg),
                    backgroundColor: 'rgba(99, 102, 241, 0.9)',
                    borderColor: 'rgb(99, 102, 241)',
                    borderWidth: 1,
                    borderRadius: 4,
                    stack: 'demand',
                },
            ],
        }
    })

    const chartOptions: ChartOptions<'bar'> = {
        responsive: true,
        maintainAspectRatio: true,
        interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: {
                position: 'top',
                labels: { boxWidth: 12, padding: 16, font: { size: 12 } },
            },
            tooltip: {
                callbacks: {
                    label: (ctx) =>
                        ` ${ctx.dataset.label}: ${(ctx.raw as number).toLocaleString()} kg`,
                },
            },
        },
        scales: {
            x: {
                stacked: true,
                grid: { display: false },
                ticks: { font: { size: 11 }, maxRotation: 45 },
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

    return { chartData, chartOptions }
}
