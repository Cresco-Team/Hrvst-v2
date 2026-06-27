<script setup lang="ts">
import { Search } from 'lucide-vue-next'
import type { Component } from 'vue'
import { Button } from './ui/button'
import {
	Empty,
	EmptyContent,
	EmptyDescription,
	EmptyHeader,
	EmptyMedia,
	EmptyTitle,
} from './ui/empty'

type Props = {
	title: string
	description?: string
	icon?: Component
	button?: string
}

withDefaults(defineProps<Props>(), {
	icon: Search,
})
</script>

<template>
    <Empty class="border border-dashed mx-20">
        <EmptyHeader>
            <EmptyMedia variant="icon">
                <component :is="icon" />
            </EmptyMedia>
            <EmptyTitle>{{ title }}</EmptyTitle>
            <EmptyDescription v-if="description">
                {{ description }}
            </EmptyDescription>
        </EmptyHeader>
        <EmptyContent v-if="button">
            <Button size="sm" >
                {{ button }}
            </Button>
        </EmptyContent>
    </Empty>
</template>