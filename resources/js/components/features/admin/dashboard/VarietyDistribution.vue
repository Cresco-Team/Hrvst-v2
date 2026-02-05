<script setup lang="ts">
import { onMounted, ref, watch, onUnmounted, computed } from 'vue'
import { Gem } from 'lucide-vue-next'
import { Chart, registerables } from 'chart.js'

Chart.register(...registerables)

interface DistributionData {
    name: string
    category: string
    value: number
}

const props = defineProps<{
    data: DistributionData[]
}>()

const chartCanvas = ref<HTMLCanvasElement | null>(null)
let chartInstance: Chart | null = null

const COLORS = [
    '#3b82f6', '#10b981', '#f97316', '#ef4444', '#eab308',
    '#8b5cf6', '#06b6d4', '#14b8a6', '#84cc16', '#ec4899',
]

const totalPlantings = computed(() => {
    return props.data.reduce((sum, item) => sum + item.value, 0)
})

function createChart() {
    if (!chartCanvas.value || !props.data) return

    const ctx = chartCanvas.value.getContext('2d')
    if (!ctx) return

    if (chartInstance) {
        chartInstance.destroy()
    }

    const isDark = document.documentElement.classList.contains('dark')

    chartInstance = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: props.data.map(d => d.name),
            datasets: [{
                data: props.data.map(d => d.value),
                backgroundColor: COLORS,
                borderWidth: 3,
                borderColor: isDark ? '#1f2937' : '#ffffff',
                hoverBorderWidth: 4,
                hoverOffset: 8,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '65%',
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: isDark ? '#1f2937' : '#ffffff',
                    titleColor: isDark ? '#f9fafb' : '#111827',
                    bodyColor: isDark ? '#f9fafb' : '#111827',
                    borderColor: isDark ? '#374151' : '#e5e7eb',
                    borderWidth: 1,
                    padding: 12,
                    callbacks: {
                        label: (context) => {
                            const value = context.parsed
                            const percentage = ((value / totalPlantings.value) * 100).toFixed(1)
                            return `${context.label}: ${value} (${percentage}%)`
                        }
                    }
                }
            }
        }
    })
}

onMounted(() => {
    createChart()
})

watch(() => props.data, () => {
    createChart()
}, { deep: true })

onUnmounted(() => {
    if (chartInstance) {
        chartInstance.destroy()
    }
})
</script>

<template>
    <div class="group rounded-xl border bg-card shadow-sm transition-all hover:shadow-md">
        <div class="border-b bg-muted/30 p-4">
            <div class="flex items-center gap-2">
                <Gem class="size-4 text-purple-600 dark:text-purple-500" />
                <div>
                    <h3 class="font-semibold">Variety Distribution</h3>
                    <p class="text-xs text-muted-foreground">Top 10 by active plantings</p>
                </div>
            </div>
        </div>
        <div class="p-6">
            <div class="relative h-64">
                <canvas ref="chartCanvas"></canvas>
                
                <!-- Center label showing total -->
                <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                    <div class="text-3xl font-bold">{{ totalPlantings }}</div>
                    <div class="text-xs text-muted-foreground">Total</div>
                </div>
            </div>

            <!-- Legend -->
            <div class="mt-6 grid grid-cols-2 gap-2 text-xs">
                <div 
                    v-for="(item, index) in data" 
                    :key="index"
                    class="flex items-center gap-2"
                >
                    <div 
                        class="size-3 shrink-0 rounded-full"
                        :style="{ backgroundColor: COLORS[index % COLORS.length] }"
                    />
                    <span class="truncate">{{ item.name }}</span>
                </div>
            </div>
        </div>
    </div>
</template>
