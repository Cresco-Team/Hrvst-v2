<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import type { Component } from 'vue'

interface StatCardItem {
    label: string
    value: number | string
    description: string
    icon: Component
    iconColor?: string
    valueColor?: string
    badges?: Array<{
        text: string
        variant?: 'default' | 'secondary' | 'outline' | 'destructive'
        className?: string
    }>
}

defineProps<{
    items: StatCardItem[]
    columns?: 2 | 3 | 4
}>()
</script>

<template>
    <div 
        class="grid grid-cols-1 gap-4"
        :class="{
            'md:grid-cols-2': columns === 2,
            'md:grid-cols-3': columns === 3,
            'md:grid-cols-4': columns === 4,
        }"
    >
        <Card v-for="(item, index) in items" :key="index">
            <CardHeader class="pb-2">
                <div class="flex items-center justify-between">
                    <CardTitle class="text-sm font-medium text-muted-foreground">
                        {{ item.label }}
                    </CardTitle>
                    <component 
                        :is="item.icon" 
                        class="size-4" 
                        :class="item.iconColor || 'text-primary'" 
                    />
                </div>
            </CardHeader>
            <CardContent>
                <p 
                    class="text-2xl font-bold"
                    :class="item.valueColor"
                >
                    {{ item.value }}
                </p>
                <p class="text-xs text-muted-foreground mt-1">
                    {{ item.description }}
                </p>
                
                <!-- Optional badges -->
                <div v-if="item.badges && item.badges.length > 0" class="flex flex-wrap gap-1.5 mt-2">
                    <Badge
                        v-for="(badge, badgeIndex) in item.badges"
                        :key="badgeIndex"
                        :variant="badge.variant || 'secondary'"
                        :class="badge.className || 'text-xs gap-1'"
                    >
                        {{ badge.text }}
                    </Badge>
                </div>
            </CardContent>
        </Card>
    </div>
</template>