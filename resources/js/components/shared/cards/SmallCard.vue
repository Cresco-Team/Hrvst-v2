<script setup lang="ts">
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import type { Component } from 'vue';

const props = withDefaults(
    defineProps<{
        title?: string
        value?: number
        valueClass?: string
        subtext?: string | null
        icon?: Component
        iconClass?: string
        cardClass?: string | null
    }>(),
    {
        title: 'Card Title',
        value: 0,
        subtext: null,
        icon: undefined,
        iconClass: 'size-4',
        cardClass: "grid-span-1",
    }
)
</script>

<template>
    <Card class="gap-0 py-4 overflow-hidden justify-center hover:shadow-md transition-all" :class="cardClass">
        <CardContent class="px-4">
            <CardDescription class="text-xs">{{ title }}</CardDescription>
        </CardContent>
        <CardHeader class="px-6 flex items-end justify-between">
            <CardTitle :class="valueClass" class="text-2xl">
                {{ value }}
                <span class="text-muted-foreground font-light text-xs">
                    {{ subtext }}
                </span>
            </CardTitle>
            <component v-if="icon" :is="icon" :class="iconClass" class="size-4" />
        </CardHeader>
    </Card>
</template>