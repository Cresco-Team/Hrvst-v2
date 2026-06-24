import { BarElement, CategoryScale, Chart as ChartJS, type ChartOptions, Legend, LinearScale, Title, Tooltip,
} from 'chart.js'
import { computed, type MaybeRefOrGetter, toValue } from 'vue'
import type { MonthlyActivity } from '@/types/resources/product'

ChartJS.register(BarElement, CategoryScale, LinearScale, Title, Tooltip, Legend)

export function useMonthlyVolumeChart(activity: MaybeRefOrGetter<MonthlyActivity[] | null | undefined>) {
  const chartData = computed(() => {
    const months = toValue(activity)

    if (!months?.length) return null

    return {
      labels: months.map((m) => m.label),
      datasets: [
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
          label: 'Supply — Expired',
          data: months.map((m) => m.supply_expired_kg),
          backgroundColor: 'rgba(34, 197, 94, 0.5)',
          borderColor: 'rgb(34, 197, 94)',
          borderWidth: 1,
          borderRadius: 4,
          stack: 'supply',
        }, {
          label: 'Demand — Fulfilled',
          data: months.map((m) => m.demand_fulfilled_kg),
          backgroundColor: 'rgba(249, 115, 22, 0.9)',
          borderColor: 'rgb(249, 115, 22)',
          borderWidth: 1,
          borderRadius: 4,
          stack: 'demand',
        }, {
          label: 'Demand — Expired',
          data: months.map((m) => m.demand_expired_kg),
          backgroundColor: 'rgba(249, 115, 22, 0.5)',
          borderColor: 'rgb(249, 115, 22)',
          borderWidth: 1,
          borderRadius: 4,
          stack: 'demand',
        },
      ],
    }
  })

  const chartOptions: ChartOptions<'bar'> = {
    responsive: true,
    maintainAspectRatio: false,
    interaction: { mode: 'index', intersect: false },
    plugins: {
      legend: {
        position: 'bottom',
        labels: { boxWidth: 8, boxHeight: 8, padding: 8, font: { size: 10 } },
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
        ticks: { font: { size: 11 }, maxRotation: 45, maxTicksLimit: 6},
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
