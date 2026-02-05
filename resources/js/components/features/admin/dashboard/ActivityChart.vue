<script setup lang="ts">
import { onMounted, ref, watch, onUnmounted } from 'vue'
import { MessageSquare } from 'lucide-vue-next'
import { Chart, registerables } from 'chart.js'

Chart.register(...registerables)

interface ActivityData {
    date: string
    messages: number
    conversations: number
}

const props = defineProps<{
    data: ActivityData[]
}>()

const chartCanvas = ref<HTMLCanvasElement | null>(null)
let chartInstance: Chart | null = null

function createChart() {
    if (!chartCanvas.value || !props.data) return

    const ctx = chartCanvas.value.getContext('2d')
    if (!ctx) return

    if (chartInstance) {
        chartInstance.destroy()
    }

    const isDark = document.documentElement.classList.contains('dark')

    chartInstance = new Chart(ctx, {
        type: 'line',
        data: {
            labels: props.data.map(d => d.date),
            datasets: [
                {
                    label: 'Messages',
                    data: props.data.map(d => d.messages),
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    borderWidth: 2.5,
                    pointRadius: 0,
                    pointHoverRadius: 6,
                    pointHoverBackgroundColor: '#3b82f6',
                    pointHoverBorderColor: '#ffffff',
                    pointHoverBorderWidth: 2,
                    yAxisID: 'y',
                },
                {
                    label: 'Conversations',
                    data: props.data.map(d => d.conversations),
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.4,
                    borderWidth: 2.5,
                    pointRadius: 0,
                    pointHoverRadius: 6,
                    pointHoverBackgroundColor: '#10b981',
                    pointHoverBorderColor: '#ffffff',
                    pointHoverBorderWidth: 2,
                    yAxisID: 'y1',
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
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
                    border: {
                        display: false
                    },
                    grid: {
                        display: false,
                    },
                    ticks: {
                        color: isDark ? '#9ca3af' : '#6b7280',
                        maxRotation: 0,
                        autoSkip: true,
                        maxTicksLimit: 10,
                    }
                },
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
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
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    beginAtZero: true,
                    border: {
                        display: false
                    },
                    grid: {
                        drawOnChartArea: false,
                    },
                    ticks: {
                        color: isDark ? '#9ca3af' : '#6b7280',
                        padding: 8,
                    }
                },
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
                <MessageSquare class="size-4 text-blue-600 dark:text-blue-500" />
                <div>
                    <h3 class="font-semibold">Conversation Activity</h3>
                    <p class="text-xs text-muted-foreground">30-day messaging trends</p>
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
