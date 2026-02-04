<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Users, MessageSquare, UserCheck, TrendingUp } from 'lucide-vue-next'

interface Summary {
    total_dealers: number
    active_this_week: number
    total_conversations: number
    new_this_month: number
}

defineProps<{
    summary: Summary
}>()
</script>

<template>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
        <!-- Total Dealers -->
        <Card>
            <CardHeader class="pb-2">
                <div class="flex items-center justify-between">
                    <CardTitle class="text-sm font-medium text-muted-foreground">
                        Total Dealers
                    </CardTitle>
                    <Users class="size-4 text-primary" />
                </div>
            </CardHeader>
            <CardContent>
                <p class="text-2xl font-bold">{{ summary.total_dealers }}</p>
                <p class="text-xs text-muted-foreground mt-1">Approved dealers</p>
            </CardContent>
        </Card>

        <!-- Active This Week -->
        <Card>
            <CardHeader class="pb-2">
                <div class="flex items-center justify-between">
                    <CardTitle class="text-sm font-medium text-muted-foreground">
                        Active This Week
                    </CardTitle>
                    <UserCheck class="size-4 text-primary" />
                </div>
            </CardHeader>
            <CardContent>
                <p class="text-2xl font-bold text-green-600 dark:text-green-500">
                    {{ summary.active_this_week }}
                </p>
                <p class="text-xs text-muted-foreground mt-1">Active dealers</p>
            </CardContent>
        </Card>

        <!-- Total Conversations -->
        <Card>
            <CardHeader class="pb-2">
                <div class="flex items-center justify-between">
                    <CardTitle class="text-sm font-medium text-muted-foreground">
                        Total Conversations
                    </CardTitle>
                    <MessageSquare class="size-4 text-primary" />
                </div>
            </CardHeader>
            <CardContent>
                <p class="text-2xl font-bold">{{ summary.total_conversations }}</p>
                <p class="text-xs text-muted-foreground mt-1">All time</p>
            </CardContent>
        </Card>

        <!-- New This Month -->
        <Card>
            <CardHeader class="pb-2">
                <div class="flex items-center justify-between">
                    <CardTitle class="text-sm font-medium text-muted-foreground">
                        New This Month
                    </CardTitle>
                    <TrendingUp class="size-4 text-primary" />
                </div>
            </CardHeader>
            <CardContent>
                <p class="text-2xl font-bold">{{ summary.new_this_month }}</p>
                <p class="text-xs text-muted-foreground mt-1">New dealers</p>
            </CardContent>
        </Card>
    </div>
</template>
