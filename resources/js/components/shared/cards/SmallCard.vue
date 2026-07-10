<script setup lang="ts">
import type { Component } from 'vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'

withDefaults(
	defineProps<{
		title?: string
		value?: number | string
		valueClass?: string
		subtext?: string | null
		icon?: Component
		iconClass?: string  // controls both size and color — don't hardcode size-4 in template
		cardClass?: string | null
		subtextBelow?: boolean  // when true, subtext renders as its own line beneath the value
	}>(),
	{
		value: 0,
		subtext: null,
		icon: undefined,
		iconClass: 'size-6',  // sensible default; callers override with e.g. 'size-4 text-green-500'
		cardClass: 'grid-span-1',
		subtextBelow: false,
	},
)
</script>

<template>
    <Card
        class="gap-0 py-4 overflow-hidden justify-center hover:shadow-md transition-all"
        :class="cardClass"
    >
        <CardContent class="px-4">
            <CardDescription class="text-xs line-clamp-1">{{ title }}</CardDescription>
        </CardContent>
        <CardHeader class="px-6 flex items-end justify-between">
            <CardTitle
                :class="valueClass"
                class="text-xl space-x-1"
            >
                <span class="font-mono">{{ value }}</span>
                <span
                    v-if="!subtextBelow"
                    class="text-muted-foreground font-light text-xs truncate"
                >
                    <slot name="subtext">{{ subtext }}</slot>
                </span>
            </CardTitle>
            <component
                :is="icon"
                v-if="icon"
                :class="iconClass"
            />
        </CardHeader>
        <CardContent
            v-if="subtextBelow"
            class="px-6 pt-0"
        >
            <span class="text-muted-foreground font-light text-xs truncate">
                <slot name="subtext">{{ subtext }}</slot>
            </span>
        </CardContent>
    </Card>
</template>
