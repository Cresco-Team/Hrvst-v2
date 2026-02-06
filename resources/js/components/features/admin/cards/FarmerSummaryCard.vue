<script setup lang="ts">
import { computed } from 'vue'
import StatCard from '@/components/shared/cards/StatCard.vue'
import { Users, Sprout, Clock, TrendingUp } from 'lucide-vue-next'

interface Summary {
    total_farmers: number
    total_active_plantings: number
    harvesting_soon: number
    average_plantings_per_farmer: number
}

const props = defineProps<{
    summary: Summary
}>()

const items = computed(() => [
    {
        label: 'Total Farmers',
        value: props.summary.total_farmers,
        description: 'Approved farmers',
        icon: Users,
    },
    {
        label: 'Active Plantings',
        value: props.summary.total_active_plantings,
        description: 'Currently growing',
        icon: Sprout,
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
        label: 'Avg. Plantings',
        value: props.summary.average_plantings_per_farmer,
        description: 'Per farmer',
        icon: TrendingUp,
    },
])
</script>

<template>
    <StatCard :items="items" :columns="4" />
</template>
