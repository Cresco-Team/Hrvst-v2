import { CategoryScale, Chart as ChartJS, type ChartOptions, Filler, Legend, LinearScale, LineElement, PointElement, Title, Tooltip,
} from 'chart.js'
import { computed, type MaybeRefOrGetter, toValue } from 'vue'
import type { PriceHistoryResource } from '@/types/resources/product'

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend, Filler)

export function usePriceHistoryChart(prices: MaybeRefOrGetter<PriceHistoryResource[] | null | undefined>) {
  const chartData = computed(() => {
    const raw = toValue(prices)

    if (!raw?.length) return null

    const sorted = [...raw].sort(
      (a, b) => new Date(a.recorded_at).getTime() - new Date(b.recorded_at).getTime(),
    )

    return {
      labels: sorted.map((p) => p.recorded_at),
      datasets: [
        {
          label: 'Max (₱/kg)',
          data: sorted.map((p) => p.price_max),
          borderColor: 'rgb(99, 102, 241)',
          backgroundColor: 'rgba(99, 102, 241, 0.08)',
          fill: true,
          tension: 0.4,
          pointRadius: 3,
          pointHoverRadius: 5,
        },
        {
          label: 'Min (₱/kg)',
          data: sorted.map((p) => p.price_min),
          borderColor: 'rgb(34, 197, 94)',
          backgroundColor: 'rgba(34, 197, 94, 0.08)',
          fill: true,
          tension: 0.4,
          pointRadius: 3,
          pointHoverRadius: 5,
        },
      ],
    }
  })

  const chartOptions: ChartOptions<'line'> = {
    responsive: true,
    maintainAspectRatio: false,
    interaction: { mode: 'index', intersect: false },
    plugins: {
      legend: {
        position: 'top',
        labels: { boxWidth: 12, padding: 16, font: { size: 12 } },
      },
      tooltip: {
        callbacks: { label: (ctx) => ` ₱${(ctx.raw as number).toFixed(2)}` },
      },
    },
    scales: {
      x: {
        grid: { display: false },
        ticks: { font: { size: 11 }, maxRotation: 45, maxTicksLimit: 6 },
      },
      y: {
        grid: { color: 'rgba(0,0,0,0.05)' },
        ticks: { font: { size: 11 }, callback: (value) => `₱${value}` },
      },
    },
  }

  return { chartData, chartOptions }
}
