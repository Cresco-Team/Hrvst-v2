<script setup lang="ts">
import { Line } from 'vue-chartjs'
import EmptyState from '@/components/EmptyState.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { useRegistrationTrendChart } from '@/composables/useRegistrationTrendChart'
import type { RegistrationTrendPoint } from '@/types'

const props = defineProps<{
    trends: RegistrationTrendPoint[]
}>()

const { chartData, chartOptions } = useRegistrationTrendChart(() => props.trends)
</script>

<template>
    <Card class="rounded-none border-0 shadow-none sm:rounded sm:border sm:shadow-sm">
        <CardHeader class="px-3 py-3 sm:px-6 sm:py-6">
            <CardTitle>Registrations — Last 12 Months</CardTitle>
        </CardHeader>
        <CardContent class="px-0 sm:px-6">
            <div
                v-if="chartData"
                class="relative h-64 w-full sm:h-72"
            >
                <Line
                    :data="chartData"
                    :options="chartOptions"
                />
            </div>
            <EmptyState
                v-else
                title="No registrations recorded yet"
            />
        </CardContent>
    </Card>
</template>
