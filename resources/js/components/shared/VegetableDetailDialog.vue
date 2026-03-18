<script setup lang="ts">
import {
	CategoryScale,
	Chart as ChartJS,
	type ChartOptions,
	Filler,
	Legend,
	LinearScale,
	LineElement,
	PointElement,
	Title,
	Tooltip,
} from 'chart.js'
import { AlertCircle, Leaf } from 'lucide-vue-next'
import { computed } from 'vue'
import { Line } from 'vue-chartjs'
import { Badge } from '@/components/ui/badge'
import {
	Dialog,
	DialogContent,
	DialogDescription,
	DialogTitle,
} from '@/components/ui/dialog'
import { Separator } from '@/components/ui/separator'
import type { CatalogVariety } from '@/types/shared/vegetables'

ChartJS.register(
	CategoryScale,
	LinearScale,
	PointElement,
	LineElement,
	Title,
	Tooltip,
	Legend,
	Filler,
)

const props = defineProps<{
	open: boolean
	variety: CatalogVariety | null
}>()

defineEmits<{
	'update:open': [value: boolean]
}>()

const freshnessConfig = {
	recent: {
		label: 'Recently Updated',
		class:
			'bg-green-500/10 text-green-700 dark:text-green-400 border-green-500/20',
	},
	stable: {
		label: 'Stable',
		class: 'bg-blue-500/10 text-blue-700 dark:text-blue-400 border-blue-500/20',
	},
	'very stable': {
		label: 'Older Price',
		class:
			'bg-amber-500/10 text-amber-700 dark:text-amber-400 border-amber-500/20',
	},
	stale: {
		label: 'Stale Price',
		class: 'bg-red-500/10 text-red-700 dark:text-red-400 border-red-500/20',
	},
} as const

// recent_prices arrive oldest → newest from the service (sorted ascending)
const chartData = computed(() => {
	if (!props.variety?.recent_prices?.length) return null

	const prices = props.variety.recent_prices

	return {
		labels: prices.map((p) => p.recorded_at),
		datasets: [
			{
				label: 'Max (₱/kg)',
				data: prices.map((p) => p.price_max),
				borderColor: 'rgb(99, 102, 241)',
				backgroundColor: 'rgba(99, 102, 241, 0.08)',
				fill: true,
				tension: 0.4,
				pointRadius: 3,
				pointHoverRadius: 5,
			},
			{
				label: 'Min (₱/kg)',
				data: prices.map((p) => p.price_min),
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
	maintainAspectRatio: true,
	interaction: {
		mode: 'index',
		intersect: false,
	},
	plugins: {
		legend: {
			position: 'top',
			labels: {
				boxWidth: 12,
				padding: 16,
				font: { size: 12 },
			},
		},
		tooltip: {
			callbacks: {
				label: (ctx) => ` ₱${(ctx.raw as number).toFixed(2)}`,
			},
		},
	},
	scales: {
		x: {
			grid: { display: false },
			ticks: { font: { size: 11 }, maxRotation: 45 },
		},
		y: {
			grid: { color: 'rgba(0,0,0,0.05)' },
			ticks: {
				font: { size: 11 },
				callback: (value) => `₱${value}`,
			},
		},
	},
}

// Table shows newest → oldest (reverse of chart)
const tableRows = computed(() =>
	props.variety ? [...props.variety.recent_prices].reverse() : [],
)
</script>

<template>
  <Dialog :open="open" @update:open="$emit('update:open', $event)">
    <DialogContent class="flex max-h-[90vh] w-full max-w-2xl flex-col overflow-hidden p-0">
      <!-- Header with image -->
      <div class="relative shrink-0">
        <div class="relative h-40 w-full overflow-hidden bg-muted">
          <img
            v-if="variety?.image_url"
            :src="variety.image_url"
            :alt="`${variety.vegetable.name} ${variety.name}`"
            class="size-full object-cover"
          />
          <div v-else class="flex size-full items-center justify-center">
            <Leaf class="size-16 text-muted-foreground/20" />
          </div>
          <!-- Gradient overlay for readability -->
          <div class="absolute inset-0 bg-linear-to-t from-background/90 via-background/20 to-transparent" />
        </div>

        <div class="absolute bottom-0 left-0 p-5">
          <div class="flex items-center gap-2">
            <Badge variant="secondary" class="text-xs">
              {{ variety?.vegetable.category.name }}
            </Badge>
            <Badge
              v-if="variety?.latest_price"
              variant="outline"
              class="text-xs"
              :class="freshnessConfig[variety.latest_price.freshness]?.class"
            >
              {{ freshnessConfig[variety.latest_price.freshness]?.label }}
            </Badge>
          </div>
          <DialogTitle class="mt-1 text-xl font-bold leading-tight text-foreground">
            {{ variety?.vegetable.name }} <span class="text-primary">{{ variety?.name }}</span>
          </DialogTitle>
          <DialogDescription class="sr-only">
            Variety details and price history for {{ variety?.vegetable.name }} {{ variety?.name }}
          </DialogDescription>
        </div>
      </div>

      <!-- Scrollable body -->
      <div class="flex-1 overflow-y-auto">
        <div class="flex flex-col gap-5 p-5">

          <!-- Quick stats row -->
          <div class="grid grid-cols-3 gap-3">
            <div class="rounded-lg border bg-muted/30 p-3 text-center">
              <p class="text-xs text-muted-foreground">Min Price</p>
              <p class="mt-0.5 font-mono text-base font-semibold text-green-600 dark:text-green-400">
                {{ variety?.latest_price ? `₱${variety.latest_price.price_min.toFixed(2)}` : '—' }}
              </p>
            </div>
            <div class="rounded-lg border bg-muted/30 p-3 text-center">
              <p class="text-xs text-muted-foreground">Max Price</p>
              <p class="mt-0.5 font-mono text-base font-semibold text-indigo-600 dark:text-indigo-400">
                {{ variety?.latest_price ? `₱${variety.latest_price.price_max.toFixed(2)}` : '—' }}
              </p>
            </div>
          </div>

          <Separator />

          <!-- Price trend chart -->
          <div>
            <h4 class="mb-3 text-sm font-semibold">Price Trend</h4>

            <div v-if="chartData" class="rounded-lg border p-3">
              <Line :data="chartData" :options="chartOptions" />
            </div>

            <div v-else class="flex items-center gap-2 rounded-lg border border-dashed p-6 text-sm text-muted-foreground">
              <AlertCircle class="size-4 shrink-0" />
              No price history available for this variety.
            </div>
          </div>

          <Separator />

          <!-- Price history table -->
          <div>
            <h4 class="mb-3 text-sm font-semibold">
              Price History
              <span class="ml-1.5 font-normal text-muted-foreground">(last {{ tableRows.length }} records)</span>
            </h4>

            <div v-if="tableRows.length" class="overflow-hidden rounded-lg border">
              <table class="w-full text-sm">
                <thead class="bg-muted/50">
                  <tr>
                    <th class="px-3 py-2 text-left text-xs font-medium text-muted-foreground">Date</th>
                    <th class="px-3 py-2 text-right text-xs font-medium text-muted-foreground">Min (₱/kg)</th>
                    <th class="px-3 py-2 text-right text-xs font-medium text-muted-foreground">Max (₱/kg)</th>
                    <th class="px-3 py-2 text-right text-xs font-medium text-muted-foreground">Avg (₱/kg)</th>
                  </tr>
                </thead>
                <tbody class="divide-y">
                  <tr
                    v-for="(entry, index) in tableRows"
                    :key="index"
                    class="transition-colors hover:bg-muted/30"
                    :class="{ 'bg-primary/5': index === 0 }"
                  >
                    <td class="px-3 py-2 text-xs text-muted-foreground">
                      {{ entry.recorded_at }}
                      <span v-if="index === 0" class="ml-1.5 rounded bg-primary/10 px-1 py-0.5 text-[10px] font-medium text-primary">
                        latest
                      </span>
                    </td>
                    <td class="px-3 py-2 text-right font-mono text-xs text-green-700 dark:text-green-400">
                      {{ entry.price_min.toFixed(2) }}
                    </td>
                    <td class="px-3 py-2 text-right font-mono text-xs text-indigo-700 dark:text-indigo-400">
                      {{ entry.price_max.toFixed(2) }}
                    </td>
                    <td class="px-3 py-2 text-right font-mono text-xs text-foreground">
                      {{ ((entry.price_min + entry.price_max) / 2).toFixed(2) }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div v-else class="flex items-center gap-2 rounded-lg border border-dashed p-6 text-sm text-muted-foreground">
              <AlertCircle class="size-4 shrink-0" />
              No price records found.
            </div>
          </div>

        </div>
      </div>
    </DialogContent>
  </Dialog>
</template>
