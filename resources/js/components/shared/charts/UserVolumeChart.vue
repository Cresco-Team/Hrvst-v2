<script setup lang="ts">
import { Bar } from 'vue-chartjs'
import EmptyState from '@/components/EmptyState.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { useSingleSeriesVolumeChart } from '@/composables/useSingleSeriesVolumeChart'
import type { MonthlyVolumeData } from '@/types/resources/product'

const props = defineProps<{
    monthlyVolume: MonthlyVolumeData[]
    title?: string
}>()

const { chartData, chartOptions } = useSingleSeriesVolumeChart(() => props.monthlyVolume)
</script>

<template>
    <Card class="rounded-none border-0 shadow-none sm:rounded sm:border sm:shadow-sm">
        <CardHeader class="px-3 py-3 sm:px-6 sm:py-6">
            <CardTitle>{{ title ?? '6-Month Volume' }}</CardTitle>
        </CardHeader>
        <CardContent class="px-0 sm:px-6">
            <div
                v-if="chartData"
                class="relative h-48 w-full"
            >
                <Bar
                    :data="chartData"
                    :options="chartOptions"
                />
            </div>
            <EmptyState
                v-else
                title="No volume recorded yet"
            />
        </CardContent>
    </Card>
</template>
