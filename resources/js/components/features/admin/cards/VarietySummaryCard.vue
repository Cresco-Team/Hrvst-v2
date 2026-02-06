<script setup lang="ts">
import { computed } from 'vue'
import StatCard from '@/components/shared/cards/StatCard.vue'
import { Leaf, TrendingUp, AlertTriangle } from 'lucide-vue-next'

interface Summary {
    total_varieties: number
    total_vegetables: number
    average_weeks_to_harvest: number
    price_stats: {
        updated_week: number
        updated_month: number
        stale: number
        no_price: number
    }
}

const props = defineProps<{
    summary: Summary
}>()

const items = computed(() => [
    {
        label: 'Total Varieties',
        value: props.summary.total_varieties,
        description: 'Available for planting',
        icon: Leaf,
    },
    {
        label: 'Price Updates',
        value: props.summary.price_stats.updated_week,
        description: 'this week',
        icon: TrendingUp,
        badges: [
            {
                text: `${props.summary.price_stats.updated_month} this month`,
                className: 'text-xs gap-1',
                variant: 'secondary' as const,
            },
        ],
    },
    {
        label: 'Needs Attention',
        value: props.summary.price_stats.stale + props.summary.price_stats.no_price,
        description: 'varieties',
        icon: AlertTriangle,
        iconColor: 'text-orange-500',
        valueColor: 'text-orange-600 dark:text-orange-500',
        badges: [
            {
                text: `${props.summary.price_stats.stale} stale prices`,
                variant: 'outline' as const,
                className: 'text-xs gap-1 border-orange-200 dark:border-orange-800',
            },
            {
                text: `${props.summary.price_stats.no_price} no prices`,
                variant: 'outline' as const,
                className: 'text-xs gap-1 border-red-200 dark:border-red-800',
            },
        ],
    },
])
</script>

<template>
    <StatCard :items="items" :columns="3" />
</template>
