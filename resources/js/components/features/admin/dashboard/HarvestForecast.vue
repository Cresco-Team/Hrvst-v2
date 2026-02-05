<script setup lang="ts">
import { onMounted, ref, watch, onUnmounted, computed } from 'vue'
import { Calendar } from 'lucide-vue-next'
import { Chart, registerables } from 'chart.js'

Chart.register(...registerables)

interface ForecastData {
    week: string
    date_range: string
    [category: string]: string | number
}

const props = defineProps<{
    data: ForecastData[]
}>()

const chartCanvas = ref<HTMLCanvasElement | null>(null)
let chartInstance: Chart | null = null

const categories = computed(() => {
    if (!props.data || props.data.length === 0) return []
    const firstItem = props.data[0]
    return Object.keys(firstItem).filter(key => key !== 'week' && key !== 'date_range')
})

const categoryColors: Record<string, string> = {
    'Leafy Vegetables': '#10b981',
    'Root Vegetables': '#f97316',
    'Fruiting Vegetables': '#ef4444',
    'Bulb Vegetables': '#eab308',
    'Legumes': '#84cc16',
    'Brassicas': '#14b8a6',
}

function createChart() {
    if (!chartCanvas.value || !props.data) return

    const ctx = chartCanvas.value.getContext('2d')
    if (!ctx) return

    if (chartInstance) {
        chartInstance.destroy()
    }

    const isDark = document.documentElement.classList.contains('dark')

    const datasets = categories.value.map((category, index) => ({
        label: category,
        data: props.data.map(d => Number(d[category]) || 0),
        backgroundColor: categoryColors[category] || `hsl(${index * 60}, 70%, 50%)`,
    }))

    chartInstance = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: props.data.map(d => d.week),
            datasets: datasets
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom',
                    labels: {
                        color: isDark ? '#9ca3af' : '#6b7280',
                        boxWidth: 12,
                        padding: 10,
                        font: {
                            size: 11
                        },
                        usePointStyle: true,
                        pointStyle: 'circle',
                    }
                },
                tooltip: {
                    backgroundColor: isDark ? '#1f2937' : '#ffffff',
                    titleColor: isDark ? '#f9fafb' : '#111827',
                    bodyColor: isDark ? '#f9fafb' : '#111827',
                    borderColor: isDark ? '#374151' : '#e5e7eb',
                    borderWidth: 1,
                    padding: 12,
                }
            },
            scales: {
                x: {
                    stacked: true,
                    border: {
                        display: false
                    },
                    grid: {
                        display: false,
                    },
                    ticks: {
                        color: isDark ? '#9ca3af' : '#6b7280',
                        font: {
                            size: 10
                        }
                    }
                },
                y: {
                    stacked: true,
                    beginAtZero: true,
                    border: {
                        display: false
                    },
                    grid: {
                        color: isDark ? 'rgba(55, 65, 81, 0.3)' : 'rgba(243, 244, 246, 0.8)',
                    },
                    ticks: {
                        color: isDark ? '#9ca3af' : '#6b7280',
                        padding: 8,
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
                <Calendar class="size-4 text-orange-600 dark:text-orange-500" />
                <div>
                    <h3 class="font-semibold">Harvest Forecast</h3>
                    <p class="text-xs text-muted-foreground">12-week projection by category</p>
                </div>
            </div>
        </div>
        <div class="p-6">
            <div class="h-64">
                <canvas ref="chartCanvas"></canvas>
            </div>
        </div>
    </div>
</template>
