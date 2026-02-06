<script setup lang="ts">
import { computed } from 'vue'
import StatCard from '@/components/shared/cards/StatCard.vue'
import { Users, MessageSquare, UserCheck, TrendingUp } from 'lucide-vue-next'

interface Summary {
    total_dealers: number
    active_this_week: number
    total_conversations: number
    new_this_month: number
}

const props = defineProps<{
    summary: Summary
}>()

const items = computed(() => [
    {
        label: 'Total Dealers',
        value: props.summary.total_dealers,
        description: 'Approved dealers',
        icon: Users,
    },
    {
        label: 'Active This Week',
        value: props.summary.active_this_week,
        description: 'Active dealers',
        icon: UserCheck,
        valueColor: 'text-green-600 dark:text-green-500',
    },
    {
        label: 'Total Conversations',
        value: props.summary.total_conversations,
        description: 'All time',
        icon: MessageSquare,
    },
    {
        label: 'New This Month',
        value: props.summary.new_this_month,
        description: 'New dealers',
        icon: TrendingUp,
    },
])
</script>

<template>
    <StatCard :items="items" :columns="4" />
</template>
