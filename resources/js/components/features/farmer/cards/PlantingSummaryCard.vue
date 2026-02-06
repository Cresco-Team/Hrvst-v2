<script setup lang="ts">
import { computed } from 'vue'
import StatCard from '@/components/shared/cards/StatCard.vue'
import { Sprout, Weight, Clock, CheckCircle } from 'lucide-vue-next'

interface Summary {
    total_active: number
    total_weight_active: number
    harvesting_soon: number
    harvested_this_month: number
}

const props = defineProps<{
    summary: Summary
}>()

const items = computed(() => [
    {
        label: 'Active Plantings',
        value: props.summary.total_active,
        description: 'Currently growing',
        icon: Sprout,
    },
    {
        label: 'Total Weight',
        value: `${props.summary.total_weight_active} kg`,
        description: 'Active plantings',
        icon: Weight,
    },
    {
        label: 'Harvesting Soon',
        value: props.summary.harvesting_soon,
        description: 'Within 7 days',
        icon: Clock,
        iconColor: 'text-orange-500',
        valueColor: 'text-orange-600 dark:text-orange-500',
    },
    {
        label: 'Harvested This Month',
        value: props.summary.harvested_this_month,
        description: 'Completed harvests',
        icon: CheckCircle,
        iconColor: 'text-green-500',
        valueColor: 'text-green-600 dark:text-green-500',
    },
])
</script>

<template>
    <StatCard :items="items" :columns="4" />
</template>
