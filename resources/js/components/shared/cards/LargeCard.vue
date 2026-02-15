<script setup lang="ts">
import Badge from '@/components/ui/badge/Badge.vue';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardDescription from '@/components/ui/card/CardDescription.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import type { Component } from 'vue';

const props = withDefaults(
    defineProps<{
        title?: string;
        value?: number;
        subtext?: string;
        change?: string;
        badge?: Record<string, any> | null;
        trendColor?: string | null;
        cardClass?: string | null;
        icon?: Component;
        iconColor?: string;
    }>(),
    {
        title: 'Card Title',
        value: 0,
        subtext: '',
        change: '',
        badge: null,
        trendColor: null,
        cardClass: "grid-span-1",
        icon: undefined,
        iconColor: 'text-primary',
    }
)
</script>

<template>
    <Card class="gap-2 overflow-hidden hover:shadow-md transition-all" :class="cardClass">
        <CardContent class="flex items-center justify-between">
            <CardDescription>{{ title }}</CardDescription>
            <Badge v-if="change" variant="outline">
                <component 
                    :is="badge" 
                    :class="trendColor" 
                />
                <span :class="trendColor">{{ change }}</span>
            </Badge>
            <component 
                v-else-if="icon" 
                :is="icon" 
                :class="iconColor" 
            />

        </CardContent>
        <CardHeader>
            <CardTitle class="text-2xl md:text-3xl lg:text-5xl">
                {{ value }}
                <span class="text-muted-foreground font-light text-sm">
                    {{ subtext }}
                </span>
            </CardTitle>
        </CardHeader>
    </Card>
</template>